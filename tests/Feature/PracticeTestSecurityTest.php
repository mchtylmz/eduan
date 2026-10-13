<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Enums\YesNoEnum;
use App\Livewire\Frontend\Tests\SolveForm;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class PracticeTestSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private Exam $exam;

    private User $user;

    private array $questions = [];

    private array $answers = [];

    private int $initialDetailCount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initialDetailCount = ExamResultDetail::count();
        Livewire::withoutLazyLoading();
        $this->travelTo(now()->startOfSecond());
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $lesson = Lesson::create(['code' => 'practice-lesson', 'name' => 'Practice lesson']);
        $topic = Topic::create(['code' => 'practice-topic', 'lesson_id' => $lesson->id, 'title' => 'Practice topic']);
        $this->exam = Exam::create([
            'locale' => 'nl', 'code' => 'practice-security', 'name' => 'Practice',
            'status' => StatusEnum::ACTIVE, 'visibility' => VisibilityEnum::LOGGED,
        ]);
        foreach ([1, 2] as $index) {
            $question = Question::create([
                'locale' => 'nl', 'lesson_id' => $lesson->id, 'topic_id' => $topic->id,
                'title' => 'Question '.$index, 'status' => StatusEnum::ACTIVE,
                'attachment' => 'uploads/logo.png', 'solution' => 'uploads/logo.png',
            ]);
            $this->exam->questions()->attach($question->id, [
                'order' => $index, 'lesson_id' => $lesson->id, 'topic_id' => $topic->id,
            ]);
            $this->questions[] = $question;
            $this->answers[] = [
                Answer::create(['question_id' => $question->id, 'title' => 'Correct', 'correct' => YesNoEnum::YES]),
                Answer::create(['question_id' => $question->id, 'title' => 'Wrong', 'correct' => YesNoEnum::NO]),
            ];
        }
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_guest_and_premium_without_permission_cannot_mount_solver(): void
    {
        auth()->logout();
        Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false])->assertForbidden();
        $this->actingAs($this->user);
        $this->exam->update(['visibility' => VisibilityEnum::PREMIUM]);
        Livewire::test(SolveForm::class, ['exam' => $this->exam->fresh(), 'lazy' => false])->assertForbidden();
        $this->assertSame($this->initialDetailCount, ExamResultDetail::count());
    }

    public function test_forged_correct_flag_is_rejected_without_writing(): void
    {
        Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false])
            ->set('results.1.isCorrect', true)
            ->assertStatus(422);
        $this->assertSame($this->initialDetailCount, ExamResultDetail::count());
    }

    public function test_answer_from_another_question_is_rejected(): void
    {
        Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false])
            ->set('results.1.userAnswer', (string) $this->answers[1][0]->id)
            ->assertStatus(422);
        $this->assertSame($this->initialDetailCount, ExamResultDetail::count());
    }

    public function test_member_answers_are_scored_on_server_and_completed_only_after_all_questions(): void
    {
        $form = Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false]);
        $this->travel(4)->seconds();
        $form->set('results.1.userAnswer', (string) $this->answers[0][1]->id);
        $result = ExamResult::where('exam_id', $this->exam->id)->where('user_id', $this->user->id)->firstOrFail();
        $this->assertTrue(YesNoEnum::NO->is($result->completed));
        $this->assertTrue(YesNoEnum::NO->is($result->details()->first()->correct));
        $this->assertSame(4, $result->details()->first()->time);
        $form->set('results.1.userAnswer', $this->answers[0][0]->id);
        $this->assertSame($this->answers[0][1]->id, $result->details()->first()->answer_id);
        $form->call('nextPage')->set('results.2.userAnswer', $this->answers[1][0]->id);
        $result->refresh();
        $this->assertTrue(YesNoEnum::YES->is($result->completed));
        $this->assertSame(1, $result->correct_count);
        $this->assertSame(1, $result->incorrect_count);
        $form->call('saveAnswer');
        $this->assertSame(1, ExamResult::where('exam_id', $this->exam->id)->where('user_id', $this->user->id)->count());
        Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false])
            ->assertSet('resultId', 0)
            ->set('results.1.userAnswer', $this->answers[0][0]->id)
            ->assertStatus(200);
        $this->assertSame(2, ExamResult::where('exam_id', $this->exam->id)->where('user_id', $this->user->id)->count());
    }

    public function test_direct_call_rechecks_permission_and_early_completion_is_rejected(): void
    {
        $form = Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false]);
        $form->call('complete')->assertStatus(409);
        $form = Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false]);
        $this->exam->update(['status' => StatusEnum::PASSIVE]);
        $form->call('saveAnswer')->assertForbidden();
        $this->assertSame($this->initialDetailCount, ExamResultDetail::count());
    }

    public function test_question_helper_cannot_disclose_the_answer_model_by_direct_call(): void
    {
        $this->expectException(MethodNotFoundException::class);
        Livewire::test(SolveForm::class, ['exam' => $this->exam, 'lazy' => false])->call('question');
    }
}
