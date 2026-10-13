<?php

namespace Tests\Feature;

use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Livewire\Users\ResultStatsCustomTable;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class StudentStatsAccessTest extends TestCase
{
    use DatabaseTransactions;

    private User $student;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $teacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->other = User::factory()->create();
        foreach (['dashboard:access', 'users:view', 'stats:view', 'user-type:teacher'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $teacher->givePermissionTo(['dashboard:access', 'users:view', 'stats:view', 'user-type:teacher']);
        $teacher->students()->attach($this->student);
        $this->actingAs($teacher);
        $lesson = Lesson::create(['code' => 'stats-lesson', 'name' => 'Stats']);
        $topic = Topic::create(['code' => 'stats-topic', 'title' => 'Stats', 'lesson_id' => $lesson->id]);
        $exam = Exam::create(['code' => 'stats-exam', 'name' => 'Practice', 'locale' => 'nl', 'visibility' => VisibilityEnum::LOGGED]);
        $test = Test::create(['code' => 'stats-test', 'name' => 'Timed', 'locale' => 'nl', 'created_by' => $teacher->id]);
        foreach ([$this->student, $this->other] as $user) {
            $result = ExamResult::create(['user_id' => $user->id, 'exam_id' => $exam->id, 'question_count' => 1, 'correct_count' => 1, 'completed' => YesNoEnum::YES]);
            $result->details()->create(['question_id' => 1, 'answer_id' => 1, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'correct' => YesNoEnum::YES]);
            TestsResult::create(['user_id' => $user->id, 'test_id' => $test->id, 'question_count' => 1, 'correct_count' => 1, 'completed' => YesNoEnum::YES]);
        }
    }

    public function test_global_stats_with_no_user_show_and_export_only_own_students(): void
    {
        $table = new ResultStatsCustomTable;
        $table->mount();
        $table->resultPercent = 100;
        foreach (['filterTopics', 'filterExams', 'filterTests'] as $method) {
            $rows = $table->$method(export: true);
            $this->assertCount(1, $rows);
            $this->assertSame($this->student->email, $rows->first()->email);
            $this->assertSame($this->student->id, $rows->first()->user_id);
        }
        $table->resultType = 'exams';
        $this->assertTrue($table->filter());
    }

    public function test_student_screen_cannot_expand_scope_with_a_changed_user_filter(): void
    {
        $table = new ResultStatsCustomTable;
        $table->mount($this->student);
        $table->userId = $this->other->id;
        $table->resultPercent = 100;
        $this->assertSame($this->student->email, $table->filterExams(export: true)->first()->email);
        $this->assertSame($this->student->id, $table->userId);
    }

    public function test_global_filter_rejects_an_unrelated_student_even_on_direct_export_query(): void
    {
        $table = new ResultStatsCustomTable;
        $table->mount();
        $table->userId = $this->other->id;
        try {
            $table->filterTests(export: true);
            $this->fail('Başka öğrenci sonucu okunamamalı.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_repeated_practice_and_timed_results_are_summed_instead_of_selecting_one_attempt(): void
    {
        foreach ([$this->student->examResults(), $this->student->testResults()] as $relation) {
            $first = $relation->firstOrFail();
            $first->update(['question_count' => 10, 'correct_count' => 7, 'incorrect_count' => 3]);
            if ($first instanceof TestsResult) {
                $first->update(['point' => 18]);
            }
            $second = $first->replicate();
            $second->fill(['question_count' => 10, 'correct_count' => 5, 'incorrect_count' => 5]);
            if ($second instanceof TestsResult) {
                $second->point = 10;
            }
            $second->save();
        }
        $table = new ResultStatsCustomTable;
        $table->mount($this->student);
        $table->resultPercent = 100;
        foreach (['filterExams', 'filterTests'] as $method) {
            $rows = $table->$method(export: true);
            $this->assertCount(1, $rows);
            $row = $rows->first();
            $this->assertSame(20, (int) $row->total_questions);
            $this->assertSame(12, (int) $row->count_correct);
            $this->assertSame(8, (int) $row->count_incorrect);
            $this->assertEquals(60, $row->success_rate);
            if ($method === 'filterTests') {
                $this->assertSame(28, (int) $row->point);
            }
            $this->assertEquals($row, $table->$method()->items()[0]);
            $table->resultPercent = 59;
            $this->assertCount(0, $table->$method(export: true));
            $table->resultPercent = 100;
        }
    }

    public function test_statistics_percentage_is_weighted_by_questions_and_handles_zero_questions(): void
    {
        foreach ([$this->student->examResults(), $this->student->testResults()] as $relation) {
            $first = $relation->firstOrFail();
            $first->update(['question_count' => 10, 'correct_count' => 10, 'incorrect_count' => 0]);
            $second = $first->replicate();
            $second->fill(['question_count' => 30, 'correct_count' => 0, 'incorrect_count' => 30]);
            $second->save();
        }
        $table = new ResultStatsCustomTable;
        $table->mount($this->student);
        $table->resultPercent = 100;
        foreach (['filterExams', 'filterTests'] as $method) {
            $row = $table->$method(export: true)->first();
            $this->assertSame(40, (int) $row->total_questions);
            $this->assertEquals(25, $row->success_rate);
        }

        $this->student->examResults()->update(['question_count' => 0, 'correct_count' => 0, 'incorrect_count' => 0]);
        $this->student->testResults()->update(['question_count' => 0, 'correct_count' => 0, 'incorrect_count' => 0]);
        $this->assertCount(0, $table->filterExams(export: true));
        $this->assertCount(0, $table->filterTests(export: true));
    }
}
