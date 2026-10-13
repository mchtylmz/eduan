<?php

namespace Tests\Feature;

use App\Actions\Tests\CompleteTestResult;
use App\Actions\Tests\SaveTestResultAnswer;
use App\Actions\Tests\StartTestResult;
use App\Enums\StatusEnum;
use App\Enums\TestSectionTypeEnum;
use App\Enums\YesNoEnum;
use App\Jobs\CalculateLeagueResult;
use App\Jobs\CompleteExpiredTestResult;
use App\Livewire\Frontend\Exams\SolveForm;
use App\Models\Answer;
use App\Models\LeagueResult;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestsQuestion;
use App\Models\TestsResult;
use App\Models\TestsResultDetail;
use App\Models\TestsSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class TimedExamTest extends TestCase
{
    use DatabaseTransactions;

    private Test $test;

    private User $user;

    private array $questions = [];

    private array $sections = [];

    private array $answers = [];

    protected function setUp(): void
    {
        if (($_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE')) !== 'testing') {
            throw new \RuntimeException('TimedExamTest yalnız testing veritabanında çalışır.');
        }

        parent::setUp();
        $this->assertSame('testing', DB::connection()->getDatabaseName());
        $this->travelTo(now()->startOfSecond());
        Queue::fake([CompleteExpiredTestResult::class]);

        $this->user = User::factory()->create();
        Permission::findOrCreate('tests:solve', 'web');
        $this->user->givePermissionTo('tests:solve');
        $this->test = Test::create([
            'locale' => 'nl', 'code' => 'timed-exam', 'name' => 'Timed exam',
            'content' => '', 'status' => StatusEnum::ACTIVE, 'duration' => 120,
            'correct_point' => 3, 'incorrect_point' => -1, 'passing_score' => 60,
            'created_by' => $this->user->id,
        ]);
        $topic = TestsSection::create([
            'test_id' => $this->test->id, 'parent_id' => 0,
            'type' => TestSectionTypeEnum::TOPIC, 'name' => 'Topic', 'order' => 1,
        ]);

        foreach ([1, 2] as $index) {
            $question = Question::create([
                'locale' => 'nl', 'lesson_id' => 1, 'topic_id' => 1,
                'title' => 'Question '.$index, 'attachment' => 'uploads/logo.png',
                'solution' => 'uploads/logo.png',
            ]);
            $section = TestsSection::create([
                'test_id' => $this->test->id, 'parent_id' => $topic->id,
                'type' => TestSectionTypeEnum::QUESTION, 'order' => $index,
            ]);
            $section->setMeta('questionId', $question->id);
            TestsQuestion::create([
                'test_id' => $this->test->id, 'section_id' => $section->id,
                'question_id' => $question->id, 'lesson_id' => 1, 'topic_id' => 1,
                'order' => $index,
            ]);
            $this->questions[] = $question;
            $this->sections[] = $section;
            $this->answers[] = Answer::create([
                'question_id' => $question->id, 'title' => 'Correct', 'correct' => YesNoEnum::YES,
            ]);
        }

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function answerPath(int $index = 0): string
    {
        return 'results.0.'.$this->sections[$index]->id.'.'.$this->questions[$index]->id;
    }

    public function test_navigation_is_ready_on_first_render_and_unanswered_questions_can_be_skipped(): void
    {
        $form = Livewire::test(SolveForm::class, ['test' => $this->test]);
        $this->assertSame(['0:'.$this->sections[0]->id, '0:'.$this->sections[1]->id], $form->get('sectionNavigation'));
        $form->assertDontSee('wire:init="addSectionNavigation', false)
            ->call('nextSection')->assertSet('active.parent', $this->sections[1]->id)
            ->call('prevSection')->assertSet('active.parent', $this->sections[0]->id);
        $this->assertSame(0, $form->get('testResult')->details()->count());
        $form->call('setActive', 0, $this->sections[1]->id)->assertSet('active.parent', $this->sections[1]->id);
    }

    public function test_saved_question_state_and_timer_origin_survive_reload(): void
    {
        app()->setLocale('nl');
        $form = Livewire::test(SolveForm::class, ['test' => $this->test]);
        $result = $form->get('testResult');
        $form->assertSee('Beantwoord')->assertSee('Resterende tijd');
        $this->assertSame('blank', $this->navigationState($form, $this->sections[0]->id));
        $form->set($this->answerPath(), (string) $this->answers[0]->id);
        $this->assertSame('answered', $this->navigationState($form, $this->sections[0]->id));
        $this->travel(30)->seconds();
        $reopened = Livewire::test(SolveForm::class, ['test' => $this->test]);
        $reopened->assertSee('data-started="'.$result->created_at->getTimestampMs().'"', false)
            ->assertSee('data-expires="'.$result->expires_at->getTimestampMs().'"', false)
            ->assertSee('data-server-now="'.now()->getTimestampMs().'"', false);
        $this->assertSame('answered', $this->navigationState($reopened, $this->sections[0]->id));
        $reopened->call('uncheckAnswer', $this->questions[0]->id);
        $this->assertSame('blank', $this->navigationState($reopened, $this->sections[0]->id));
    }

    private function navigationState($form, int $sectionId): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($form->html());

        return (new \DOMXPath($document))->query('//button[@data-section-id="'.$sectionId.'"]')->item(0)->getAttribute('data-answer-state');
    }

    public function test_answered_navigation_does_not_reveal_whether_an_answer_is_correct(): void
    {
        $wrong = Answer::create([
            'question_id' => $this->questions[0]->id, 'title' => 'B', 'correct' => YesNoEnum::NO,
        ]);
        $form = Livewire::test(SolveForm::class, ['test' => $this->test])
            ->set($this->answerPath(), (string) $wrong->id);
        $navigation = function () use ($form) {
            $document = new \DOMDocument;
            @$document->loadHTML($form->html());

            return $document->saveHTML((new \DOMXPath($document))
                ->query('//button[@data-section-id="'.$this->sections[0]->id.'"]')->item(0));
        };
        $wrongState = $navigation();
        $this->assertSame('answered', $this->navigationState($form, $this->sections[0]->id));
        $form->set($this->answerPath(), (string) $this->answers[0]->id);
        $this->assertSame($wrongState, $navigation());
        $this->assertSame('answered', $this->navigationState($form, $this->sections[0]->id));
    }

    public function test_exam_screen_uses_dutch_and_french_labels_through_completion(): void
    {
        foreach (['nl' => ['Beantwoord', 'Resterende tijd', 'Examenonderdelen', 'Voltooi het examen', 'Resultaten bekijken', 'Sluiten'],
            'fr' => ['Répondu', 'Temps restant', 'Sections de l’examen', 'Terminer l’examen', 'Voir les résultats', 'Fermer']] as $locale => $labels) {
            app()->setLocale($locale);
            app('translator')->setLoaded([]);
            $form = Livewire::test(SolveForm::class, ['test' => $this->test]);
            foreach ($labels as $label) {
                $form->assertSee($label);
            }
            $form->assertDontSee('Cevaplanan')->assertDontSee('Kalan Süre')
                ->assertDontSee('Cevaplar otomatik kaydedilir')
                ->assertDontSee('Sınav tamamlanacak ve sorulara verilen yanıtlar gönderilecektir, onaylıyor musunuz?');
            $form->call('save')->assertSet('finished', true);
            $form->assertSee(__('Sınavı geçemedin, çalışmaya devam etmelisin!'));
        }
    }

    public function test_reopening_preserves_answers_and_original_deadline(): void
    {
        $form = Livewire::test(SolveForm::class, ['test' => $this->test])
            ->set($this->answerPath(), (string) $this->answers[0]->id);
        $result = TestsResult::where('test_id', $this->test->id)->where('user_id', $this->user->id)->firstOrFail();
        $deadline = $result->expires_at->timestamp;

        $this->travel(30)->seconds();
        $reopened = Livewire::test(SolveForm::class, ['test' => $this->test])
            ->assertSet($this->answerPath(), $this->answers[0]->id)
            ->assertSet('finished', false);

        $this->assertSame(1, TestsResult::where('test_id', $this->test->id)->where('user_id', $this->user->id)->count());
        $this->assertSame($deadline, $result->fresh()->expires_at->timestamp);
        Queue::assertPushed(CompleteExpiredTestResult::class, fn ($job) => $job->resultId === $result->id && $job->delay->timestamp === $deadline);

        $reopened->call('uncheckAnswer', $this->questions[0]->id)
            ->assertSet($this->answerPath(), 0);
        $this->assertSame(0, $result->details()->first()->answer_id);
        $form->call('saveAndFinish')->assertSet('finished', false);
        $this->assertTrue(YesNoEnum::NO->is($result->fresh()->completed));
    }

    public function test_job_completes_closed_exam_and_a_new_attempt_starts_from_zero(): void
    {
        Livewire::test(SolveForm::class, ['test' => $this->test])
            ->set($this->answerPath(), (string) $this->answers[0]->id);
        $result = $this->test->results()->where('user_id', $this->user->id)->firstOrFail();
        $deadline = $result->expires_at->timestamp;

        $this->travel(125)->seconds();
        $job = new CompleteExpiredTestResult($result->id);
        $job->handle();
        $result->refresh();

        $this->assertTrue(YesNoEnum::YES->is($result->completed));
        $this->assertSame($deadline, $result->completed_at->timestamp);
        $this->assertSame(120, $result->duration);
        $this->assertSame(1, $result->correct_count);
        $this->assertSame(0, $result->incorrect_count);
        $this->assertSame(3, $result->point);
        $this->assertSame(2, $result->details()->count());

        $job->handle();
        $this->assertSame($deadline, $result->fresh()->completed_at->timestamp);

        Livewire::test(SolveForm::class, ['test' => $this->test])
            ->assertSet($this->answerPath(), 0)->assertSet('finished', false);
        $this->assertSame(2, $this->test->results()->where('user_id', $this->user->id)->count());
        $next = $this->test->results()->where('user_id', $this->user->id)->latest('id')->firstOrFail();
        $this->assertSame(now()->addSeconds(120)->timestamp, $next->expires_at->timestamp);
        $this->assertSame(0, $next->details()->count());
    }

    public function test_late_answer_cannot_replace_an_answer_saved_before_expiry(): void
    {
        $result = StartTestResult::run($this->test);
        SaveTestResultAnswer::run($result, $this->sections[0]->id, $this->questions[0]->id, $this->answers[0]->id);
        $this->travel(120)->seconds();

        SaveTestResultAnswer::run($result, $this->sections[0]->id, $this->questions[0]->id, 0);

        $this->assertTrue(YesNoEnum::YES->is($result->fresh()->completed));
        $this->assertSame($this->answers[0]->id, $result->details()->where('question_id', $this->questions[0]->id)->first()->answer_id);
    }

    public function test_manual_completion_scores_answers_and_cannot_be_rewritten(): void
    {
        $wrong = Answer::create([
            'question_id' => $this->questions[1]->id, 'title' => 'Wrong', 'correct' => YesNoEnum::NO,
        ]);
        $form = Livewire::test(SolveForm::class, ['test' => $this->test])
            ->set($this->answerPath(), (string) $this->answers[0]->id)
            ->set($this->answerPath(1), (string) $wrong->id);
        $this->travel(15)->seconds();
        $form->call('save')->assertSet('finished', true);
        $result = $this->test->results()->where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(1, $result->correct_count);
        $this->assertSame(1, $result->incorrect_count);
        $this->assertSame(2, $result->point);
        $this->assertSame(15, $result->duration);
        $form->set($this->answerPath(), '0');
        $this->assertSame($this->answers[0]->id, $result->details()->where('question_id', $this->questions[0]->id)->first()->answer_id);
    }

    public function test_answer_question_ownership_and_permission_are_enforced(): void
    {
        $result = StartTestResult::run($this->test);
        $this->assertRejected(422, fn () => SaveTestResultAnswer::run(
            $result, $this->sections[0]->id, $this->questions[0]->id, $this->answers[1]->id));
        $this->assertRejected(422, fn () => SaveTestResultAnswer::run(
            $result, $this->sections[1]->id, $this->questions[0]->id, $this->answers[0]->id));
        $this->assertSame(0, $result->details()->count());

        $this->user->revokePermissionTo('tests:solve');
        $this->assertRejected(403, fn () => SaveTestResultAnswer::run(
            $result, $this->sections[0]->id, $this->questions[0]->id, $this->answers[0]->id));
    }

    public function test_another_student_cannot_write_to_the_attempt(): void
    {
        $result = StartTestResult::run($this->test);
        $other = User::factory()->create();
        $other->givePermissionTo('tests:solve');
        $this->actingAs($other);

        try {
            SaveTestResultAnswer::run($result, $this->sections[0]->id, $this->questions[0]->id, $this->answers[0]->id);
            $this->fail('Başka öğrencinin denemesine yazılamamalı.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(0, $result->details()->count());
        }
    }

    public function test_league_includes_only_completed_timed_exam_attempts(): void
    {
        $this->user->update(['league_approval' => 1]);
        $completed = StartTestResult::run($this->test);
        SaveTestResultAnswer::run($completed, $this->sections[0]->id, $this->questions[0]->id, $this->answers[0]->id);
        CompleteTestResult::run($completed);

        $other = User::factory()->create(['league_approval' => 1]);
        $other->givePermissionTo('tests:solve');
        $this->actingAs($other);
        $ongoing = StartTestResult::run($this->test);
        SaveTestResultAnswer::run($ongoing, $this->sections[0]->id, $this->questions[0]->id, $this->answers[0]->id);

        settings()->leagueResultModel = TestsResultDetail::class;
        $job = new CalculateLeagueResult(now()->subMinute()->toDateTimeString(), now()->addMinute()->toDateTimeString());
        $job->handle();

        $rows = LeagueResult::whereIn('user_id', [$this->user->id, $other->id])->get();
        $this->assertCount(1, $rows);
        $this->assertSame($this->user->id, $rows->first()->user_id);
        $this->assertSame(3, $rows->first()->total_score);
    }

    private function assertRejected(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('İstek reddedilmeliydi.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }
}
