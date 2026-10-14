<?php

namespace Tests\Feature;

use App\Actions\Tests\CompleteTestResult;
use App\Actions\Tests\SaveTestResultAnswer;
use App\Enums\StatusEnum;
use App\Enums\TestSectionTypeEnum;
use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Helpers\StudyAutomaticWork;
use App\Livewire\Frontend\Study\StudyForm;
use App\Livewire\Frontend\Tests\SolveForm;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\Language;
use App\Models\Question;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\Test;
use App\Models\TestsQuestion;
use App\Models\TestsResult;
use App\Models\TestsSection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StudyAutomaticIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Europe/Brussels']);
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        foreach (['study:access', 'tests:solve', 'exams:solve'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->owner = User::factory()->create();
        $this->owner->givePermissionTo(['study:access', 'tests:solve', 'exams:solve']);
        $this->actingAs($this->owner);
        settings()->set(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-14']);
        settings()->save();
    }

    private function historical(bool $timed, array $values = [], ?int $attempt = null, ?User $user = null): int
    {
        $results = $timed ? 'tests_results' : 'exam_results';
        $details = $timed ? 'tests_result_details' : 'exam_result_details';
        $foreign = $timed ? 'tests_result_id' : 'exam_result_id';
        $time = '2026-10-12 12:00:00';
        $attempt ??= DB::table($results)->insertGetId(['user_id' => ($user ?? $this->owner)->id,
            $timed ? 'test_id' : 'exam_id' => 1, 'completed' => 0, 'created_at' => $time, 'updated_at' => $time]);

        return DB::table($details)->insertGetId($values + [$foreign => $attempt,
            ...($timed ? ['section_id' => 1, 'point' => 0] : []), 'question_id' => 1, 'answer_id' => 1,
            'correct' => 0, 'lesson_id' => 1, 'topic_id' => 1, 'time' => 0,
            'created_at' => $time, 'updated_at' => $time, 'study_answered_at' => null]);
    }

    private function timed(): array
    {
        $test = Test::create(['locale' => 'nl', 'code' => (string) Str::uuid(), 'name' => 'Study integration',
            'content' => '', 'status' => StatusEnum::ACTIVE, 'duration' => 3600, 'correct_point' => 3,
            'incorrect_point' => -1, 'passing_score' => 60, 'created_by' => $this->owner->id]);
        $question = Question::create(['locale' => 'nl', 'lesson_id' => 1, 'topic_id' => 1,
            'title' => 'Integration question', 'attachment' => 'uploads/logo.png', 'solution' => 'uploads/logo.png']);
        $section = TestsSection::create(['test_id' => $test->id, 'parent_id' => 0, 'type' => TestSectionTypeEnum::QUESTION, 'order' => 1]);
        TestsQuestion::create(['test_id' => $test->id, 'section_id' => $section->id,
            'question_id' => $question->id, 'lesson_id' => 1, 'topic_id' => 1, 'order' => 1]);
        $answer = Answer::create(['question_id' => $question->id, 'title' => 'Answer', 'correct' => YesNoEnum::YES]);
        $wrong = Answer::create(['question_id' => $question->id, 'title' => 'Wrong', 'correct' => YesNoEnum::NO]);
        $result = TestsResult::create(['user_id' => $this->owner->id, 'test_id' => $test->id,
            'question_count' => 1, 'completed' => YesNoEnum::NO, 'expires_at' => now()->addWeek()]);

        return [$result, $section, $question, $answer, $wrong];
    }

    public function test_partial_completed_repeated_correct_and_incorrect_attempts_all_count(): void
    {
        $this->historical(false);
        $this->historical(false, ['correct' => 1]);
        $this->historical(true, ['correct' => 2]);
        $id = $this->historical(true, ['correct' => 0]);
        $parent = DB::table('tests_result_details')->where('id', $id)->value('tests_result_id');
        DB::table('tests_results')->where('id', $parent)->update(['completed' => 1]);
        $this->assertSame([['activity_date' => '2026-10-12', 'automatic_tests' => 2, 'automatic_exams' => 2]], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
        $this->assertSame(0, StudyActivityEntry::where('user_id', $this->owner->id)->count());
    }

    public function test_historical_created_at_is_used_even_before_activation_and_grading_does_not_move_it(): void
    {
        $this->historical(true, ['created_at' => '2026-10-09 23:59:59', 'updated_at' => '2026-10-14 12:00:00']);
        $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
        $this->assertSame('2026-10-09', StudyAutomaticWork::daily($this->owner, '2026-10-03')[0]['activity_date']);
        $this->assertSame(1, StudyAutomaticWork::daily($this->owner, '2026-10-03')[0]['automatic_exams']);
    }

    public function test_latest_untimed_slot_wins_and_a_clear_does_not_resurrect_a_duplicate(): void
    {
        $first = $this->historical(false);
        $parent = DB::table('exam_result_details')->where('id', $first)->value('exam_result_id');
        $this->historical(false, ['created_at' => '2026-10-13 12:00:00'], $parent);
        $rows = StudyAutomaticWork::daily($this->owner, '2026-10-10');
        $this->assertSame(1, $rows[0]['automatic_tests']);
        $this->assertSame('2026-10-13', $rows[0]['activity_date']);
        $this->historical(false, ['answer_id' => 0], $parent);
        $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
    }

    public function test_clock_overrides_creation_and_week_boundary_is_exclusive(): void
    {
        $this->historical(true, ['study_answered_at' => '2026-10-17 00:00:00']);
        $this->historical(false, ['study_answered_at' => '2026-10-16 23:59:59']);
        $this->assertSame(1, StudyAutomaticWork::daily($this->owner, '2026-10-10')[0]['automatic_tests']);
        $this->assertSame(1, StudyAutomaticWork::daily($this->owner, '2026-10-17')[0]['automatic_exams']);
    }

    public function test_timed_answer_retry_change_clear_and_reanswer_keep_one_slot_and_move_its_week(): void
    {
        [$result, $section, $question, $answer, $wrong] = $this->timed();
        $this->travelTo(now()->setDateTime(2026, 10, 16, 23, 59, 0));
        SaveTestResultAnswer::run($result, $section->id, $question->id, $answer->id);
        $firstTime = $result->details()->first()->study_answered_at;
        $this->travelTo(now()->addMinutes(2));
        SaveTestResultAnswer::run($result, $section->id, $question->id, $answer->id);
        $this->assertEquals($firstTime, $result->details()->first()->study_answered_at);
        SaveTestResultAnswer::run($result, $section->id, $question->id, $wrong->id);
        $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
        $this->assertSame(1, StudyAutomaticWork::daily($this->owner, '2026-10-17')[0]['automatic_exams']);
        SaveTestResultAnswer::run($result, $section->id, $question->id, 0);
        $this->assertNull($result->details()->first()->study_answered_at);
        $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-17'));
        SaveTestResultAnswer::run($result, $section->id, $question->id, $answer->id);
        $this->assertSame(1, $result->details()->count());
        $this->assertSame(1, StudyAutomaticWork::daily($this->owner, '2026-10-17')[0]['automatic_exams']);
    }

    public function test_completion_and_repeated_completion_preserve_answer_time_and_grading(): void
    {
        [$result, $section, $question, $answer] = $this->timed();
        SaveTestResultAnswer::run($result, $section->id, $question->id, $answer->id);
        $before = $result->details()->first()->study_answered_at;
        $this->travelTo(now()->addHour());
        $completed = CompleteTestResult::run($result);
        $this->assertEquals($before, $result->details()->first()->study_answered_at);
        $this->assertSame(1, $completed->correct_count);
        $this->assertSame(3, $completed->point);
        $this->assertTrue(YesNoEnum::YES->is($completed->completed));
        CompleteTestResult::run($result);
        SaveTestResultAnswer::run($result, $section->id, $question->id, 0);
        $this->assertEquals($before, $result->details()->first()->study_answered_at);
        $this->assertSame(1, StudyAutomaticWork::daily($this->owner, '2026-10-10')[0]['automatic_exams']);
    }

    public function test_deadline_cannot_accept_a_new_answer_and_blank_completion_counts_nothing(): void
    {
        [$result, $section, $question, $answer] = $this->timed();
        $result->update(['expires_at' => now()]);
        SaveTestResultAnswer::run($result, $section->id, $question->id, $answer->id);
        $this->assertTrue(YesNoEnum::YES->is($result->fresh()->completed));
        $this->assertSame(0, $result->details()->first()->answer_id);
        $this->assertNull($result->details()->first()->study_answered_at);
        $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
    }

    public function test_untimed_real_save_records_time_and_a_new_attempt_counts_again(): void
    {
        [, , $question, $answer] = $this->timed();
        $exam = Exam::create(['locale' => 'nl', 'code' => (string) Str::uuid(), 'name' => 'Study test',
            'content' => '', 'status' => StatusEnum::ACTIVE, 'visibility' => VisibilityEnum::LOGGED, 'created_by' => $this->owner->id]);
        $exam->questions()->attach($question->id, ['lesson_id' => 1, 'topic_id' => 1, 'order' => 1]);
        $form = Livewire::withoutLazyLoading()->test(SolveForm::class, ['exam' => $exam]);
        $form->set('results.1.userAnswer', $answer->id)->call('saveAnswer');
        $first = $exam->results()->where('user_id', $this->owner->id)->first()->details()->first();
        $this->assertNotNull($first->study_answered_at);
        $this->travelTo(now()->addMinute());
        $form->call('saveAnswer');
        $this->assertEquals($first->study_answered_at, $first->fresh()->study_answered_at);
        Livewire::withoutLazyLoading()->test(SolveForm::class, ['exam' => $exam])
            ->set('results.1.userAnswer', $answer->id)->call('saveAnswer');
        $this->assertSame(2, StudyAutomaticWork::daily($this->owner, '2026-10-10')[0]['automatic_tests']);
    }

    public function test_blank_or_deleted_attempts_are_excluded_and_counts_are_owner_scoped(): void
    {
        $this->historical(false, ['answer_id' => 0]);
        $this->historical(true, user: User::factory()->create());
        $id = $this->historical(true);
        $parent = DB::table('tests_result_details')->where('id', $id)->value('tests_result_id');
        DB::table('tests_results')->where('id', $parent)->update(['deleted_at' => now()]);
        $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
        try {
            StudyAutomaticWork::daily(User::factory()->create(), '2026-10-10');
            $this->fail('Other owner must be denied.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_disabled_module_reads_no_answer_tables_but_answer_save_still_works(): void
    {
        [$result, $section, $question, $answer] = $this->timed();
        settings()->set('studyTrackingEnabled', '0');
        settings()->save();
        DB::enableQueryLog();
        try {
            $this->assertSame([], StudyAutomaticWork::daily($this->owner, '2026-10-10'));
            $this->assertCount(0, collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'result_details')));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        SaveTestResultAnswer::run($result, $section->id, $question->id, $answer->id);
        $this->assertNotNull($result->details()->first()->study_answered_at);
    }

    public function test_combined_study_screen_keeps_sources_separate_without_manual_or_target_writes(): void
    {
        $this->historical(false);
        $this->historical(true);
        $before = StudyActivityEntry::where('user_id', $this->owner->id)->count();
        $targetCount = StudyWeeklyTarget::where('user_id', $this->owner->id)->count();
        $form = Livewire::test(StudyForm::class, ['weekStart' => '2026-10-10']);
        $this->assertSame($before, StudyActivityEntry::where('user_id', $this->owner->id)->count());
        $days = $form->viewData('days');
        $this->assertSame($targetCount, StudyWeeklyTarget::where('user_id', $this->owner->id)->count());
        $this->assertSame(2, $days->first()['questions']);
        $this->assertSame(0, $days->first()['manual_questions']);
        $form->set('activityDate', '2026-10-14')->set('activityQuestions', 50)->call('saveActivity')->assertHasNoErrors();
        $this->assertSame(52, $form->viewData('days')->sum('questions'));
        $this->assertSame(1, StudyActivityEntry::where('user_id', $this->owner->id)->count());
    }
}
