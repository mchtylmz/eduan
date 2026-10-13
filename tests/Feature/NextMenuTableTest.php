<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Ai\AnswerVoteTable;
use App\Livewire\Leagues\LeagueTable;
use App\Livewire\Modal;
use App\Livewire\Offcanvas;
use App\Livewire\Tests\TestTable;
use App\Models\AnswerAI;
use App\Models\AnswerAIVote;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Language;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAiUsage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NextMenuTableTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('tr');
        foreach (['nl', 'tr', 'en'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => StatusEnum::ACTIVE]);
        }
        $permissions = ['dashboard:access', 'tests:view', 'tests:update', 'tests:delete', 'stats:view', 'exams:view', 'ai:view', 'questions:view', 'leagues:view', 'users:view', 'user-type:teacher', 'user-type:user', 'ai:delete', 'leagues:delete'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo(array_diff($permissions, ['user-type:teacher', 'user-type:user', 'ai:delete', 'leagues:delete']));
        $this->actingAs($this->actor)->withSession(['locale' => 'tr']);
    }

    public static function screens(): array
    {
        return [['admin.tests.index', 'tests'], ['admin.tests.results', 'test-results'], ['admin.leagues.index', 'leagues'], ['admin.stats.index', 'question-stats']];
    }

    #[DataProvider('screens')]
    public function test_screen_renders_standard_table_and_rejects_missing_permission(string $route, string $table): void
    {
        $this->get(route($route))->assertOk()->assertSee('data-server-table-page="'.$table.'"', false)->assertDontSee('wire:lazy', false);
        $permission = match ($table) {
            'tests','test-results' => 'tests:view','leagues' => 'leagues:view',default => 'stats:view'
        };
        $this->actor->revokePermissionTo($permission);
        $this->get(route($route))->assertForbidden();
    }

    public function test_timed_table_counts_sort_filter_and_readonly_actions(): void
    {
        $one = Test::factory()->create(['code' => 'NM-A', 'locale' => 'tr', 'duration' => 1800, 'created_by' => $this->actor->id]);
        $two = Test::factory()->create(['code' => 'NM-Z', 'locale' => 'tr', 'duration' => 3600, 'created_by' => $this->actor->id]);
        $one->sections()->create(['parent_id' => 0, 'name' => 'Section', 'order' => 1]);
        $one->questions()->create(['question_id' => Question::factory()->create()->id, 'section_id' => 0, 'order' => 1]);
        $p = ['locale' => 'tr', 'search' => 'NM-', 'sort' => 'duration', 'order' => 'asc', 'limit' => 1];
        $this->getJson(route('admin.tests.getData', $p))->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $one->id)
            ->assertJsonPath('rows.0.questions_count', 1)->assertJsonPath('rows.0.sections_count', 1)->assertJsonMissingPath('rows.0.content');
        $this->getJson(route('admin.tests.getData', [...$p, 'offset' => 1]))->assertJsonPath('rows.0.id', $two->id);
        $this->getJson(route('admin.tests.getData', ['sort' => 'duration;DROP']))->assertUnprocessable();
        Livewire::test(TestTable::class)->call('showDetail', $one->id)->assertSee($one->name);
        $this->actor->revokePermissionTo('tests:delete');
        Livewire::test(TestTable::class)->call('delete', $one->id)->assertForbidden();
    }

    public function test_teacher_timed_results_and_panel_do_not_leak_other_students(): void
    {
        $this->actor->givePermissionTo('user-type:teacher');
        $own = User::factory()->create();
        $other = User::factory()->create();
        $this->actor->students()->attach($own);
        $test = Test::factory()->create(['locale' => 'tr']);
        $result = $this->timedResult($own, $test);
        $foreign = $this->timedResult($other, $test);
        $q = Question::factory()->create();
        $result->details()->create(['question_id' => $q->id, 'answer_id' => 0, 'point' => 2, 'correct' => 1, 'lesson_id' => $q->lesson_id, 'topic_id' => $q->topic_id, 'section_id' => 0]);
        $this->getJson(route('admin.tests.getResultsData', ['test_id' => $test->id, 'completed' => 1]))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $result->id);
        $this->getJson(route('admin.tests.getResultDetailsData', ['tests_result_id' => $result->id]))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.point', 2);
        $this->getJson(route('admin.tests.getResultDetailsData', ['tests_result_id' => $foreign->id]))->assertNotFound();
        Livewire::test(Offcanvas::class)->call('openLearningTable', 'tests.results-detail-table', $result->id)->assertSet('show', true);
        Livewire::test(Offcanvas::class)->call('openLearningTable', 'tests.results-detail-table', $foreign->id)->assertNotFound();
    }

    public function test_ai_activity_filters_delete_boundary_and_cache_refresh(): void
    {
        $answer = AnswerAI::create(['user_id' => $this->actor->id, 'lesson_id' => 0, 'topic_id' => 0, 'question_id' => 0, 'locale' => 'tr', 'title' => 'NM AI']);
        $vote = AnswerAIVote::create(['answer_ai_id' => $answer->id, 'user_id' => $this->actor->id, 'vote' => 2]);
        UserAiUsage::create(['answer_ai_id' => $answer->id, 'user_id' => $this->actor->id, 'usage' => 3, 'remaining' => 7, 'usage_date' => today()]);
        $p = ['answer_ai_id' => $answer->id];
        $this->getJson(route('admin.ai.getVotesData', $p))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.vote', 2)->assertJsonPath('rows.0.can_delete', false)->assertJsonMissingPath('rows.0.password');
        $this->getJson(route('admin.ai.getUsagesData', [...$p, 'date' => today()->format('Y-m-d')]))->assertOk()->assertJsonPath('rows.0.usage', 3);
        DB::table('answer_a_i_votes')->where('id', $vote->id)->update(['vote' => 5]);
        $this->getJson(route('admin.ai.getVotesData', $p))->assertJsonPath('rows.0.vote', 2);
        $this->getJson(route('admin.ai.getVotesData', [...$p, 'refresh' => 1]))->assertJsonPath('rows.0.vote', 5);
        Livewire::test(AnswerVoteTable::class, ['answerAiId' => $answer->id])->call('delete', $vote->id)->assertForbidden();
        $this->actor->givePermissionTo('ai:delete');
        $second = AnswerAI::create(['user_id' => $this->actor->id, 'lesson_id' => 0, 'topic_id' => 0, 'question_id' => 0, 'locale' => 'tr', 'title' => 'NM Other AI']);
        Livewire::test(AnswerVoteTable::class, ['answerAiId' => $second->id])->call('delete', $vote->id)->assertNotFound();
        $this->get(route('admin.ai.edit', ['answerAI' => $answer->id, 'tab' => 'votes']))->assertOk()->assertSee('ai-votes-table', false);
        $this->get(route('admin.ai.edit', ['answerAI' => $answer->id, 'tab' => 'usages']))->assertOk()->assertSee('ai-usages-table', false);
    }

    public function test_league_panel_preserves_number_then_position_and_teacher_scope(): void
    {
        $this->actor->givePermissionTo('user-type:teacher');
        $student = User::factory()->create();
        $this->actor->students()->attach($student);
        $league = League::create(['code' => 'NM League', 'label' => 'tests_result_details', 'start_at' => now()->subDays(7), 'end_at' => now(), 'calculated_at' => now()]);
        foreach ([[2, 1], [1, 2], [1, 1]] as [$number,$position]) {
            $member = User::factory()->create();
            $this->actor->students()->attach($member);
            LeagueResult::create(['league_id' => $league->id, 'user_id' => $member->id, 'league_number' => $number, 'position' => $position, 'total_score' => 10, 'total_correct' => 5, 'total_incorrect' => 2, 'total_duration' => 30]);
        }
        LeagueResult::create(['league_id' => $league->id, 'user_id' => $this->actor->id, 'league_number' => 1, 'position' => 1, 'total_score' => 99]);
        $this->getJson(route('admin.leagues.getResultsData', ['league_id' => $league->id]))->assertOk()->assertJsonPath('total', 3)->assertJsonPath('rows.0.league_number', 1)->assertJsonPath('rows.0.position', 1);
        $this->getJson(route('admin.leagues.getData', ['search' => 'NM League']))->assertJsonPath('rows.0.label', __('Sınav Sonuçları'));
        Livewire::test(Offcanvas::class)->call('openLearningTable', 'leagues.league-results-table', $league->id)->assertSet('show', true);
        Livewire::test(LeagueTable::class)->call('delete', $league->id)->assertForbidden();
    }

    public function test_statistics_json_and_full_excel_share_totals_filters_and_original_names(): void
    {
        $this->actor->givePermissionTo('user-type:teacher');
        $student = User::factory()->create();
        $this->actor->students()->attach($student);
        $other = User::factory()->create();
        foreach (['Original English A', 'Original English Z'] as $name) {
            $exam = Exam::factory()->create(['name' => $name, 'locale' => 'en']);
            foreach ([[10, 7, 3], [10, 5, 5]] as [$all,$correct,$incorrect]) {
                ExamResult::create(['exam_id' => $exam->id, 'user_id' => $student->id, 'question_count' => $all, 'correct_count' => $correct, 'incorrect_count' => $incorrect]);
            }
            ExamResult::create(['exam_id' => $exam->id, 'user_id' => $other->id, 'question_count' => 99, 'correct_count' => 0]);
        }
        $p = ['result_type' => 'exams', 'result_percent' => 100, 'limit' => 1, 'sort' => 'content_name', 'order' => 'asc'];
        $this->getJson(route('admin.stats.getData', $p))->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.content_name', 'Original English A')->assertJsonPath('rows.0.total_questions', '20')->assertJsonPath('rows.0.count_correct', '12');
        $download = $this->get(route('admin.stats.getExport', $p))->assertOk();
        $file = $download->baseResponse->getFile()->getPathname();
        $workbook = IOFactory::load($file);
        $rows = $workbook->getActiveSheet()->toArray();
        $workbook->disconnectWorksheets();
        $this->assertCount(3, $rows);
        $this->assertSame('Original English A', $rows[1][3]);
        $this->assertEquals(20, $rows[1][4]);
        $this->assertEquals(60, $rows[1][7]);
        $this->getJson(route('admin.stats.getData', [...$p, 'user_id' => $other->id]))->assertForbidden();
        $this->getJson(route('admin.users.getStatsData', [...$p, 'context_user_id' => $student->id, 'user_id' => $other->id]))->assertOk()->assertJsonPath('total', 2);
        $this->getJson(route('admin.stats.getData', [...$p, 'sort' => 'unknown']))->assertUnprocessable();
    }

    public function test_question_stats_scope_date_validation_and_native_sort_controls(): void
    {
        $this->actor->givePermissionTo('user-type:teacher');
        $student = User::factory()->create();
        $this->actor->students()->attach($student);
        $test = Test::factory()->create();
        $this->timedResult($student, $test);
        $p = ['started_at' => now()->subDays(1)->format('Y-m-d'), 'ended_at' => now()->addDay()->format('Y-m-d')];
        $this->getJson(route('admin.stats.getQuestionsData', $p))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.question_count', '10');
        $this->getJson(route('admin.stats.getQuestionsData', [...$p, 'ended_at' => 'wrong']))->assertUnprocessable();
        foreach (['admin.ai.index', 'admin.questions.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('data-table-sort', false)->assertSee('data-table-order', false);
        }
    }

    public function test_topic_and_timed_reports_localize_labels_preserve_names_and_invalidate_after_edit(): void
    {
        $topic = Topic::factory()->create(['title' => ['tr' => 'NM Türkçe Konu', 'en' => 'NM English Topic']]);
        $question = Question::factory()->create(['topic_id' => $topic->id]);
        $test = Test::factory()->create(['name' => 'NM Original English', 'locale' => 'en']);
        foreach ([1, 0] as $correct) {
            $result = $this->timedResult($this->actor, $test);
            $result->details()->create(['question_id' => $question->id, 'answer_id' => 0, 'point' => 2, 'correct' => $correct, 'lesson_id' => $question->lesson_id, 'topic_id' => $topic->id, 'section_id' => 0]);
        }
        $p = ['result_type' => 'topics', 'result_percent' => 100, 'topic_id' => $topic->id, 'user_id' => $this->actor->id];
        $this->getJson(route('admin.stats.getData', $p))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.content_name', 'NM Türkçe Konu')->assertJsonPath('rows.0.success_rate', '50.00');
        $p['result_type'] = 'tests';
        $this->getJson(route('admin.stats.getData', $p))->assertOk()->assertJsonPath('rows.0.content_name', $test->name)->assertJsonPath('rows.0.point', '14');
        $test->update(['name' => 'NM Renamed English']);
        $this->getJson(route('admin.stats.getData', $p))->assertJsonPath('rows.0.content_name', 'NM Renamed English');
    }

    public function test_stats_mail_modal_requires_permission_and_current_teacher_scope(): void
    {
        Permission::findOrCreate('users:send-email', 'web');
        $student = User::factory()->create();
        $emails = [['user_id' => $student->id, 'email' => $student->email]];
        Livewire::test(Modal::class)->call('openStatsMail', $emails)->assertForbidden();
        $this->actor->givePermissionTo(['users:send-email', 'user-type:teacher']);
        Livewire::test(Modal::class)->call('openStatsMail', $emails)->assertForbidden();
        $this->actor->students()->attach($student);
        Livewire::test(Modal::class)->call('openStatsMail', $emails)->assertSet('show', true)->assertDispatched('stats-mail-opened');
    }

    private function timedResult(User $user, Test $test): TestsResult
    {
        return TestsResult::create(['user_id' => $user->id, 'test_id' => $test->id, 'season_id' => 0, 'question_count' => 10, 'correct_count' => 7, 'incorrect_count' => 3, 'point' => 7, 'passing_score' => 6, 'completed' => 1, 'completed_at' => now(), 'duration' => 60]);
    }
}
