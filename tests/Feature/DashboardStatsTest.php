<?php

namespace Tests\Feature;

use App\Enums\RoleTypeEnum;
use App\Enums\StatusEnum;
use App\Helpers\DashboardStats;
use App\Models\AnswerAI;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAiUsage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.stores.dashboard' => ['driver' => 'array']]);
        $this->freezeTime();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        foreach (['dashboard:access', 'ai:view', 'users:view', 'contacts:view', 'exams:solve', RoleTypeEnum::USER->value, RoleTypeEnum::TEACHER->value] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo(['dashboard:access', 'ai:view', 'users:view', 'contacts:view']);
        $this->actingAs($this->actor);
    }

    public function test_dashboard_renders_statistics_in_the_initial_html_with_native_charts_without_lazy_requests(): void
    {
        $this->get(route('admin.home.index'))->assertOk()
            ->assertSee(__('dashboard.new_students'))->assertSee('data-dashboard-ranking="results"', false)
            ->assertSee('dashboard-chart-plot', false)->assertSee(__('dashboard.top_five'))
            ->assertDontSee('wire:lazy', false)->assertDontSee('apexcharts')->assertDontSee('chart-render');
        $data = DashboardStats::get();
        $this->assertArrayNotHasKey('password', $data['new_users'][0]);
        $this->assertArrayNotHasKey('content', $data['hits'][0] ?? []);
    }

    public function test_teacher_ai_topics_are_merged_and_cache_does_not_cross_teachers(): void
    {
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $student = $this->student();
        $other = $this->student();
        $this->actor->students()->attach($student);
        $lesson = Lesson::factory()->create(['name' => ['nl' => 'Dashboard lesson']]);
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id, 'title' => ['nl' => 'Samengevoegd onderwerp', 'en' => 'Merged topic']]);
        $this->usage($student, $topic, 4);
        $this->usage($student, $topic, 7);
        $this->usage($other, $topic, 20);
        $data = DashboardStats::get();
        $this->assertCount(1, $data['ai']);
        $this->assertSame(11, $data['ai'][0]['hits']);
        $this->assertSame('Samengevoegd onderwerp', $data['ai'][0]['name']);
        $this->assertArrayNotHasKey('new_users', $data);
        $this->assertNull($data['messages']);

        $teacher = User::factory()->create();
        $teacher->givePermissionTo(['dashboard:access', 'ai:view', RoleTypeEnum::TEACHER->value]);
        $teacher->students()->attach($other);
        $this->actingAs($teacher);
        $this->assertSame(20, DashboardStats::get()['ai'][0]['hits']);
        $this->actingAs($this->actor);
        app()->setLocale('en');
        $this->assertSame('Merged topic', DashboardStats::get()['ai'][0]['name']);
    }

    public function test_recent_active_students_include_premium_and_assignment_changes_update_scope(): void
    {
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $normal = $this->student();
        $premium = $this->student();
        $premium->givePermissionTo('exams:solve');
        $old = $this->student(['created_at' => now()->subDays(8)]);
        $passive = $this->student(['status' => StatusEnum::PASSIVE]);
        $this->student(); // Active student outside this teacher scope.
        $this->actor->students()->attach([$normal->id, $premium->id, $old->id, $passive->id]);
        $data = DashboardStats::get();
        $this->assertSame(3, $data['registered']); // Existing teacher total includes premium students.
        $this->assertSame(1, $data['premium']);
        $this->assertSame(2, $data['new_students']);
        $this->actor->students()->detach($normal);
        $this->assertSame(1, DashboardStats::get()['new_students']);
    }

    public function test_all_attempt_totals_remain_and_warm_cache_skips_aggregate_queries_for_an_hour(): void
    {
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $student = $this->student();
        $this->actor->students()->attach($student);
        $exam = Exam::factory()->create(['locale' => 'nl', 'created_by' => $this->actor->id]);
        foreach ([[10, 7, 3, 1], [10, 2, 0, 0]] as [$questions, $correct, $incorrect, $completed]) {
            ExamResult::create(['user_id' => $student->id, 'exam_id' => $exam->id, 'question_count' => $questions,
                'correct_count' => $correct, 'incorrect_count' => $incorrect, 'completed' => $completed]);
        }
        $data = DashboardStats::get();
        $this->assertSame(20, $data['results'][0]['question_count']);
        $this->assertSame(9, $data['results'][0]['correct_count']);
        $this->assertSame(3, $data['results'][0]['incorrect_count']);
        Cache::flush(); // Existing broad cleanup must not clear the dashboard store.
        DB::enableQueryLog();
        $this->assertSame($data, DashboardStats::get());
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/(?:exam_results|user_ai_usages|from `exams`|from `lessons`)/', $query['query']);
        }
        ExamResult::create(['user_id' => $student->id, 'exam_id' => $exam->id, 'question_count' => 10]);
        $this->assertSame(20, DashboardStats::get()['results'][0]['question_count']);
        $this->travel(3601)->seconds();
        $this->assertSame(30, DashboardStats::get()['results'][0]['question_count']);
    }

    public function test_permissions_are_checked_before_cached_data_and_ai_visibility_changes(): void
    {
        $this->assertNotNull(DashboardStats::get()['ai']);
        $this->actor->revokePermissionTo('ai:view');
        $this->assertNull(DashboardStats::get()['ai']);
        $this->actor->revokePermissionTo('dashboard:access');
        $this->get(route('admin.home.index'))->assertForbidden();
    }

    public function test_student_card_filters_match_counters_and_keep_teacher_scope(): void
    {
        $normal = $this->student(['created_at' => now()->subDays(2)]);
        $premium = $this->student();
        $premium->givePermissionTo('exams:solve');
        $old = $this->student(['created_at' => now()->subDays(8)]);
        $passive = $this->student(['status' => StatusEnum::PASSIVE]);
        $this->student();
        foreach ([false, true] as $teacher) {
            if ($teacher) {
                $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
                $this->actor->students()->attach([$normal->id, $premium->id, $old->id, $passive->id]);
            }
            $data = DashboardStats::get();
            $filters = [
                'registered' => ['student_group' => $teacher ? null : 'normal'],
                'premium' => ['student_group' => 'premium'],
                'new_students' => ['student_group' => 'all', 'registered_since' => $data['new_students_from']],
            ];
            foreach ($filters as $counter => $filter) {
                $response = $this->getJson(route('admin.users.getData', ['status' => 'active', ...$filter]))->assertOk();
                $response->assertJsonPath('total', $data[$counter]);
                $this->get(route('admin.home.index'))->assertSee(route('admin.users.index', ['status' => 'active', ...$filter]));
            }
        }
        $this->getJson(route('admin.users.getData', ['status' => 'active', 'student_group' => 'all', 'registered_since' => now()->format('Y-m-d\TH:i:s')]))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $premium->id);
        $this->getJson(route('admin.users.getData', ['student_group' => 'administrator']))->assertUnprocessable();
        $this->getJson(route('admin.users.getData', ['registered_since' => 'not-a-date']))->assertUnprocessable();
        $this->actor->revokePermissionTo('users:view');
        $this->getJson(route('admin.users.getData', ['student_group' => 'premium']))->assertForbidden();
    }

    public function test_chart_limit_empty_zero_values_and_registration_date_projection(): void
    {
        for ($i = 0; $i < 6; $i++) {
            Exam::factory()->create(['status' => StatusEnum::ACTIVE]);
        }
        $data = DashboardStats::get();
        $this->assertCount(5, $data['hits']);
        $this->assertArrayHasKey('created_at', $data['new_users'][0]);
        $this->view('backend.home.partials.ranking', ['rows' => [], 'kind' => 'ai', 'title' => 'Chart', 'icon' => 'fa-eye', 'metric' => 'Views'])
            ->assertSee(__('dashboard.empty'))->assertDontSee('dashboard-chart-plot');
        $this->view('backend.home.partials.ranking', ['rows' => [['name' => '<script>unsafe</script>', 'question_count' => 0, 'correct_count' => 0, 'incorrect_count' => 0]],
            'kind' => 'results', 'title' => 'Chart', 'icon' => 'fa-eye', 'metric' => 'Questions'])
            ->assertSee('width: 0%', false)->assertDontSee('<script>unsafe</script>', false)->assertSee(__('Doğru'))->assertSee(__('Yanlış'));
        $this->view('backend.home.partials.users', ['rows' => [$data['new_users'][0]], 'title' => 'Users', 'lastLogins' => false])
            ->assertSee(dateFormat($data['new_users'][0]['created_at'], 'd M Y, H:i'));
    }

    private function student(array $attributes = []): User
    {
        $student = User::factory()->create(['status' => StatusEnum::ACTIVE, ...$attributes]);
        $student->givePermissionTo(RoleTypeEnum::USER->value);

        return $student;
    }

    private function usage(User $student, Topic $topic, int $count): void
    {
        $answer = AnswerAI::create(['user_id' => $student->id, 'lesson_id' => $topic->lesson_id, 'topic_id' => $topic->id,
            'question_id' => 0, 'locale' => 'nl', 'title' => 'Dashboard fixture']);
        UserAiUsage::create(['user_id' => $student->id, 'answer_ai_id' => $answer->id, 'usage' => $count, 'remaining' => 0, 'usage_date' => now()]);
    }
}
