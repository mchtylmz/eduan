<?php

namespace Tests\Feature;

use App\Actions\Study\EnsureStudyWeek;
use App\Enums\RoleTypeEnum;
use App\Enums\StatusEnum;
use App\Helpers\StudyAnalytics;
use App\Livewire\Frontend\Study\StudyReport;
use App\Models\Answer;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Season;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\UsesStudyTimezone;
use Tests\TestCase;

class StudyAnalyticsTest extends TestCase
{
    use DatabaseTransactions;
    use UsesStudyTimezone;

    private User $owner;

    private Season $season;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['study:access', ...RoleTypeEnum::values()] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->owner = $this->student();
        $this->actingAs($this->owner);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01', 'studyMinimumTopicQuestions' => '10']);
        settings()->save();
        $this->season = Season::create(['name' => 'Study year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => StatusEnum::ACTIVE]);
    }

    private function student(): User
    {
        $user = User::factory()->create(['status' => StatusEnum::ACTIVE]);
        $user->givePermissionTo(['study:access', RoleTypeEnum::USER->value]);

        return $user;
    }

    private function target(User $user, array $values = ['questions' => 100], string $date = '2026-10-10'): void
    {
        StudyWeeklyTarget::create($values + ['user_id' => $user->id, 'study_week_id' => EnsureStudyWeek::run($date)->id]);
    }

    private function work(User $user, int $questions = 0, int $pages = 0, int $minutes = 0, string $date = '2026-10-14'): void
    {
        StudyActivityEntry::create(['user_id' => $user->id, 'study_week_id' => EnsureStudyWeek::run($date)->id,
            'recorded_by_id' => $user->id, 'activity_date' => $date, 'questions' => $questions, 'pages' => $pages, 'minutes' => $minutes]);
    }

    public function test_week_trend_and_full_period_have_real_amounts_and_uncapped_percentages(): void
    {
        $this->target($this->owner, ['questions' => 100, 'pages' => 50, 'minutes' => 60]);
        $this->work($this->owner, 150, 50, 120);
        $this->target($this->owner, ['questions' => 100], '2026-10-03');
        $this->work($this->owner, 75, 0, 0, '2026-10-09');
        $report = StudyAnalytics::report($this->owner, '2026-10-10', $this->season);
        $this->assertSame(150.0, $report['current']['rates']['overall']);
        $this->assertCount(4, $report['trend']);
        $this->assertSame(75, $report['trend'][2]['actual']['questions']);
        $this->assertSame('2025-12-27', $report['history'][0]['start']);
        $this->assertSame('2027-01-01', end($report['history'])['end']);
        $this->assertCount(53, $report['history']);
    }

    public function test_no_target_no_activity_zero_entry_and_low_progress_are_distinct(): void
    {
        $this->assertSame('no_target', StudyAnalytics::report($this->owner, '2026-10-10', null)['current']['state']);
        $this->target($this->owner);
        $this->assertSame('no_activity', StudyAnalytics::report($this->owner, '2026-10-10', null)['current']['state']);
        $this->work($this->owner);
        $this->assertSame('zero_activity', StudyAnalytics::report($this->owner, '2026-10-10', null)['current']['state']);
        $this->work($this->owner, 20);
        $this->assertSame('low', StudyAnalytics::report($this->owner, '2026-10-10', null)['current']['state']);
    }

    public function test_evaluation_days_are_scoped_to_owner_and_selected_week_and_match_weekly_amounts(): void
    {
        $this->target($this->owner, ['questions' => 100, 'pages' => 0, 'minutes' => 60]);
        $this->work($this->owner, 40, 20, 60, '2026-10-13');
        $this->work($this->owner, 70, 10, 30);
        $this->work($this->owner, 999, 999, 999, '2026-10-09');
        $this->work($this->student(), 777);
        $report = StudyAnalytics::report($this->owner, '2026-10-10', $this->season);
        $this->assertSame(['2026-10-13', '2026-10-14'], array_column($report['days'], 'activity_date'));
        foreach (['questions', 'pages', 'minutes', 'fragments'] as $field) {
            $this->assertSame($report['current']['actual'][$field], array_sum(array_column($report['days'], $field)));
        }
        Livewire::test(StudyReport::class, ['weekStart' => '2026-10-10'])
            ->assertSee('study-evaluation-content', false)->assertSee('110%')->assertSee('150%')
            ->assertDontSee('>INF<', false)->assertDontSee('>NAN<', false);
    }

    public function test_empty_evaluation_keeps_seven_days_and_does_not_invent_zero_target_rates(): void
    {
        $report = StudyAnalytics::report($this->owner, '2026-10-10', null);
        $this->assertSame([], $report['days']);
        $form = Livewire::test(StudyReport::class, ['weekStart' => '2026-10-10']);
        $form->assertSee('data-activity-date="2026-10-10"', false)->assertSee('data-activity-date="2026-10-16"', false)
            ->assertSee(__('study.no_target'))->assertDontSee('>INF<', false)->assertDontSee('>NAN<', false);
        $this->assertSame(7, substr_count($form->html(), 'data-activity-date="'));
    }

    public function test_review_rings_keep_zero_missing_goals_and_real_overachievement_distinct(): void
    {
        $this->target($this->owner, ['questions' => 100, 'pages' => 0, 'minutes' => 60]);
        $this->work($this->owner, 0, 20, 90);
        $html = Livewire::test(StudyReport::class, ['weekStart' => '2026-10-10'])->html();
        foreach (['questions' => '0', 'pages' => '', 'minutes' => '150'] as $field => $rate) {
            $this->assertMatchesRegularExpression('/data-ring="'.$field.'" data-rate="'.$rate.'"/', $html);
            preg_match('/data-ring="'.$field.'".*?<\/g>/s', $html, $group);
            if ($field === 'minutes') {
                $this->assertStringContainsString('stroke-dasharray="100 100"', $group[0]);
            } else {
                $this->assertStringNotContainsString('study-ring-amount', $group[0]);
            }
        }
        $this->assertStringContainsString('150%', $html);
    }

    public function test_comparison_excludes_passive_no_goal_and_staff_and_deduplicates_teacher_students(): void
    {
        $a = $this->student();
        $b = $this->student();
        $inactive = $this->student();
        $noGoal = $this->student();
        foreach ([$this->owner, $a, $b, $inactive] as $user) {
            $this->target($user);
        }
        $inactive->update(['status' => StatusEnum::PASSIVE]);
        $this->work($this->owner, 100);
        $this->work($a, 50);
        $this->work($b, 25);
        $this->work($noGoal, 500);
        $t1 = $this->student();
        $t1->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $t2 = $this->student();
        $t2->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $t1->students()->attach([$this->owner->id, $a->id]);
        $t2->students()->attach([$this->owner->id, $b->id]);
        $this->target($t1);
        $this->work($t1, 1000);
        $report = StudyAnalytics::report($this->owner, '2026-10-10', null);
        $this->assertSame(3, $report['comparisons']['all_students']['questions']['size']);
        $this->assertSame(3, $report['comparisons']['teacher_group']['questions']['size']);
        $this->assertSame(1, $report['comparisons']['teacher_group']['questions']['rank']);
        $this->assertStringNotContainsString($a->email, json_encode($report));
        $this->assertStringNotContainsString($b->name, json_encode($report));
    }

    public function test_staff_own_report_works_but_has_no_student_comparison_even_with_mixed_permissions(): void
    {
        $this->target($this->owner);
        $this->work($this->owner, 10);
        foreach ([RoleTypeEnum::TEACHER, RoleTypeEnum::ADMIN] as $type) {
            $this->owner->givePermissionTo($type->value);
            $report = StudyAnalytics::report($this->owner, '2026-10-10', null);
            $this->assertSame(10, $report['current']['actual']['questions']);
            $this->assertSame([], $report['comparisons']);
        }
    }

    public function test_ongoing_exam_accuracy_uses_chosen_answer_without_grading_or_result_writes(): void
    {
        $lesson = Lesson::create(['name' => ['nl' => 'Study maths'], 'code' => 'study-analytics']);
        $topic = Topic::create(['lesson_id' => $lesson->id, 'title' => ['nl' => 'Study fractions'], 'status' => StatusEnum::ACTIVE]);
        $result = DB::table('tests_results')->insertGetId(['user_id' => $this->owner->id, 'test_id' => 1, 'completed' => 0, 'created_at' => now(), 'updated_at' => now()]);
        for ($i = 0; $i < 10; $i++) {
            $q = Question::create(['lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'locale' => 'nl', 'solution' => 'uploads/logo.png']);
            $a = Answer::create(['question_id' => $q->id, 'title' => 'Choice', 'correct' => $i < 8 ? 1 : 0]);
            DB::table('tests_result_details')->insert(['tests_result_id' => $result, 'section_id' => $i + 1, 'question_id' => $q->id, 'answer_id' => $a->id,
                'correct' => 2, 'point' => 0, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'time' => 0, 'created_at' => now(), 'updated_at' => now(), 'study_answered_at' => now()]);
        }
        $this->target($this->owner);
        $report = StudyAnalytics::report($this->owner, '2026-10-10', null);
        $this->assertSame(10, $report['current']['actual']['questions']);
        $this->assertSame('strong', $report['topics'][0]['label']);
        $this->assertSame(80.0, $report['topics'][0]['rate']);
        $this->assertSame(10, DB::table('tests_result_details')->where('tests_result_id', $result)->where('correct', 2)->count());
        $this->assertSame(0, DB::table('tests_results')->where('id', $result)->value('completed'));
    }

    public function test_topic_minimum_and_missing_or_blank_answers_are_not_assigned_false_accuracy(): void
    {
        $result = DB::table('exam_results')->insertGetId(['user_id' => $this->owner->id, 'exam_id' => 1, 'completed' => 0]);
        foreach ([[1, 1, 1], [2, 0, 0], [3, 1, 2]] as [$question,$answer,$correct]) {
            DB::table('exam_result_details')->insert(['exam_result_id' => $result,
                'question_id' => $question, 'answer_id' => $answer, 'correct' => $correct, 'lesson_id' => 99999, 'topic_id' => 99999, 'time' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }
        $report = StudyAnalytics::report($this->owner, '2026-10-10', null);
        $this->assertSame(2, $report['current']['actual']['questions']);
        $this->assertSame('insufficient', $report['topics'][0]['label']);
        $this->assertSame(1, $report['topics'][0]['answered']);
    }

    public function test_shared_week_between_periods_has_same_total_and_target(): void
    {
        $this->target($this->owner);
        $this->work($this->owner, 20);
        $a = Season::create(['name' => 'A', 'start_date' => '2026-10-01', 'end_date' => '2026-10-14', 'status' => StatusEnum::ACTIVE]);
        $b = Season::create(['name' => 'B', 'start_date' => '2026-10-15', 'end_date' => '2026-10-31', 'status' => StatusEnum::ACTIVE]);
        $one = collect(StudyAnalytics::report($this->owner, '2026-10-10', $a)['history'])->firstWhere('start', '2026-10-10');
        $two = collect(StudyAnalytics::report($this->owner, '2026-10-10', $b)['history'])->firstWhere('start', '2026-10-10');
        $this->assertSame($one, $two);
        $this->assertSame(20, $one['actual']['questions']);
        $all = collect(StudyAnalytics::report($this->owner, '2026-10-10', null, [$b, $a])['history']);
        $this->assertCount(6, $all);
        $this->assertSame($one, $all->firstWhere('start', '2026-10-10'));
        $this->assertSame(['2026-09-26', '2026-10-03', '2026-10-10', '2026-10-17', '2026-10-24', '2026-10-31'], $all->pluck('start')->all());
    }

    public function test_report_has_initial_html_pagination_period_change_and_refresh_event(): void
    {
        session()->put('studySeasonId', $this->season->id);
        $form = Livewire::test(StudyReport::class, ['weekStart' => '2026-10-10'])->assertSet('seasonId', '')
            ->set('seasonId', $this->season->id)->assertHasNoErrors();
        $this->assertSame(20, $form->viewData('history')->count());
        $form->call('gotoPage', 2, 'studyHistoryPage');
        $this->assertSame(2, $form->viewData('history')->currentPage());
        $short = Season::create(['name' => 'Short', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'status' => StatusEnum::PASSIVE]);
        $form->set('seasonId', $short->id)->assertHasNoErrors();
        $this->assertSame(1, $form->viewData('history')->currentPage());
        $this->work($this->owner, 30);
        $form->dispatch('study-changed');
        $this->assertSame(30, $form->viewData('report')['current']['actual']['questions']);
        $form->set('seasonId', '')->assertHasNoErrors();
        $this->assertSame(1, $form->viewData('history')->currentPage());
        $this->assertGreaterThan(5, $form->viewData('history')->total());
        $this->assertSame('', session('studyHistorySeasonId'));
        $this->get(route('frontend.study', ['week' => '2026-10-10', 'season' => '', 'tab' => 'history']))->assertOk();
        $form->set('seasonId', -1)->assertHasErrors('seasonId');
    }

    public function test_report_permission_is_rechecked_and_week_cannot_be_forged(): void
    {
        $form = Livewire::test(StudyReport::class, ['weekStart' => '2026-10-10']);
        $this->owner->revokePermissionTo('study:access');
        $form->call('refreshReport')->assertForbidden();
        $this->owner->givePermissionTo('study:access');
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(StudyReport::class, ['weekStart' => '2026-10-10'])->set('weekStart', '2026-10-03');
    }

    public function test_sql_count_does_not_grow_per_week_and_other_student_work_is_not_added(): void
    {
        $other = $this->student();
        $this->work($other, 999);
        $this->work($this->owner, 10);
        DB::enableQueryLog();
        try {
            $report = StudyAnalytics::report($this->owner, '2026-10-10', $this->season);
            $queries = count(DB::getQueryLog());
            $this->assertLessThan(15, $queries);
            $this->assertSame(10, $report['current']['actual']['questions']);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
