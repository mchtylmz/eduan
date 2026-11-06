<?php

namespace Tests\Feature;

use App\Actions\Leagues\CalculateLeague;
use App\Enums\StatusEnum;
use App\Jobs\CalculateLeagueResult;
use App\Jobs\UpdateSeasonIdForResults;
use App\Livewire\Frontend\Account\FavoriteTests;
use App\Livewire\Frontend\Account\SolvedExams;
use App\Livewire\Frontend\Ai\SolutionAi;
use App\Livewire\Leagues\LeagueRunForm;
use App\Livewire\Season\SeasonForm;
use App\Models\AnswerAI;
use App\Models\Exam;
use App\Models\Language;
use App\Models\League;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Season;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StabilityReleaseTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([CalculateLeagueResult::class, UpdateSeasonIdForResults::class]);
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        app()->setLocale('nl');
        settings()->set('gptApiKey', 'fake-key-not-a-live-credential');
        settings()->set('gptModel', 'gpt-5-mini');
        settings()->set('gptEffect', 'low');
        settings()->save();
        $this->actor = User::factory()->create(['gpt_limit' => 1]);
        foreach (['dashboard:access', 'exams:view', 'exams-reviews:view', 'seasons:view', 'seasons:update', 'seasons:delete', 'leagues:view', 'leagues:run', 'ai:solution'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actingAs($this->actor);
    }

    private function question(): Question
    {
        $lesson = Lesson::factory()->create();
        $topic = Topic::factory()->create(['lesson_id' => $lesson->id]);

        return Question::factory()->create(['lesson_id' => $lesson->id, 'topic_id' => $topic->id, 'locale' => 'nl']);
    }

    private function ai(Question $question)
    {
        return Livewire::withoutLazyLoading()->test(SolutionAi::class, ['question' => $question, 'user' => $this->actor]);
    }

    public function test_ai_guest_route_redirects_and_stale_session_cannot_request(): void
    {
        $question = $this->question();
        $this->actor->givePermissionTo('ai:solution');
        $component = $this->ai($question);
        auth()->logout();
        $this->get(route('frontend.ai.solution', $question))->assertRedirect(route('login'));
        $component->call('askGpt')->assertForbidden();
        $this->assertSame(0, $this->actor->aiUsages()->count());
    }

    public function test_ai_rechecks_permission_and_changed_session_before_side_effects(): void
    {
        $question = $this->question();
        $this->actor->givePermissionTo('ai:solution');
        $component = $this->ai($question);
        $this->actor->revokePermissionTo('ai:solution');
        $component->set('remainingGptLimit', 99)->call('askGpt')->assertForbidden();
        $this->actor->givePermissionTo('ai:solution');
        $component = $this->ai($question);
        $other = User::factory()->create(['gpt_limit' => 1]);
        $other->givePermissionTo('ai:solution');
        $this->actingAs($other);
        $component->call('askGpt')->assertForbidden();
        $this->assertSame(0, $other->aiUsages()->count());
        $this->assertSame(0, $this->actor->aiUsages()->count());
    }

    public function test_ai_fresh_quota_blocks_tampered_and_stale_counters(): void
    {
        $question = $this->question();
        $this->actor->givePermissionTo('ai:solution');
        $component = $this->ai($question);
        $this->actor->aiUsages()->create(['answer_ai_id' => 0, 'usage' => 1, 'remaining' => 0, 'usage_date' => dateFormat(now(), 'Y-m-d')]);
        $component->set('remainingGptLimit', 99)->call('askGpt')->assertSet('remainingGptLimit', 0)->assertSet('disabledAskGpt', true);
        $this->assertSame(1, $this->actor->aiUsages()->count());
    }

    public function test_cached_ai_answer_consumes_one_current_day_use_and_then_stops(): void
    {
        $question = $this->question();
        $this->actor->givePermissionTo('ai:solution');
        $answer = AnswerAI::create(['question_id' => $question->id, 'user_id' => $this->actor->id, 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id, 'locale' => 'nl', 'content' => ['steps' => []]]);
        $this->actor->aiUsages()->create(['answer_ai_id' => $answer->id, 'usage' => 1, 'remaining' => 0, 'usage_date' => dateFormat(now()->subDay(), 'Y-m-d')]);
        $first = $this->ai($question);
        $second = $this->ai($question);
        $first->call('askGpt')->assertSet('remainingGptLimit', 0);
        $second->set('remainingGptLimit', 99)->call('askGpt')->assertSet('disabledAskGpt', true);
        $this->assertSame(1, $this->actor->usageGptLimit());
        $this->assertSame($answer->id, $this->actor->aiUsages()->where('usage_date', dateFormat(now(), 'Y-m-d'))->value('answer_ai_id'));
    }

    public function test_ai_quota_uses_the_same_site_day_when_utc_date_differs(): void
    {
        settings()->set('timezone', 'Europe/Amsterdam');
        settings()->save();
        $originalTimezone = date_default_timezone_get();
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
        $this->travelTo(Carbon::parse('2026-10-07 22:30:00', 'UTC'));
        try {
            $question = $this->question();
            $this->actor->givePermissionTo('ai:solution');
            AnswerAI::create(['question_id' => $question->id, 'user_id' => $this->actor->id, 'lesson_id' => $question->lesson_id, 'topic_id' => $question->topic_id, 'locale' => 'nl', 'content' => ['steps' => []]]);
            $first = $this->ai($question);
            $second = $this->ai($question);
            $first->call('askGpt')->assertSet('remainingGptLimit', 0);
            $second->set('remainingGptLimit', 99)->call('askGpt')->assertSet('disabledAskGpt', true);
            $this->assertSame(1, $this->actor->aiUsages()->count());
            $this->assertSame('2026-10-08', $this->actor->aiUsages()->value('usage_date')->toDateString());
            $this->assertSame(1, $this->actor->usageGptLimit());
            Http::assertNothingSent();
        } finally {
            $this->travelBack();
            date_default_timezone_set($originalTimezone);
        }
    }

    public function test_season_and_league_actions_recheck_operation_permissions(): void
    {
        $this->actor->givePermissionTo(['dashboard:access', 'seasons:view', 'seasons:update', 'seasons:delete', 'leagues:view', 'leagues:run']);
        $form = Livewire::test(SeasonForm::class);
        $this->actor->revokePermissionTo('seasons:update');
        $form->call('create')->assertForbidden();
        Livewire::test(SeasonForm::class)->call('save')->assertForbidden();
        $season = Season::create(['name' => 'Permission guard', 'start_date' => '2026-01-01', 'end_date' => '2026-02-01', 'status' => 'active']);
        $form = Livewire::test(SeasonForm::class)->set('seasons', [$season->toArray()]);
        $this->actor->revokePermissionTo('seasons:delete');
        $form->call('delete', 0)->assertForbidden();
        $this->assertModelExists($season);
        $league = Livewire::test(LeagueRunForm::class);
        $this->actor->revokePermissionTo('leagues:run');
        $league->call('run')->assertForbidden();
        Bus::assertNothingDispatched();
    }

    public function test_ai_missing_image_or_failed_generation_does_not_consume_quota(): void
    {
        $question = $this->question();
        $this->actor->givePermissionTo('ai:solution');
        $question->update(['attachment' => 'uploads/does-not-exist-stability.png', 'solution' => null]);
        Http::fake(['api.openai.com/*' => Http::response([], 500)]);
        $this->ai($question)->call('askGpt')->assertSet('disabledAskGpt', true);
        Http::assertNothingSent();
        $question->update(['attachment' => 'icons/fav-192.jpg', 'solution' => 'icons/fav-512.jpg']);
        $this->ai($question->fresh())->call('askGpt')->assertSet('disabledAskGpt', true);
        Http::assertSentCount(1);
        $this->assertSame(0, $this->actor->usageGptLimit());
        $this->assertFalse(AnswerAI::where('question_id', $question->id)->exists());
    }

    public function test_ai_generation_and_current_database_limit_are_authoritative(): void
    {
        $question = $this->question();
        $question->update(['attachment' => 'icons/fav-192.jpg', 'solution' => 'icons/fav-512.jpg']);
        $this->actor->givePermissionTo('ai:solution');
        $component = $this->ai($question->fresh());
        $this->actor->update(['gpt_limit' => 0]);
        Http::fake(['api.openai.com/*' => Http::response(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
            'status' => 'ready',
            'steps' => [['rule' => 'Optellen', 'definition' => '', 'explanation' => 'Tel de waarden op.', 'latex' => '1+1=2']],
            'final_answer' => 'Het resultaat is twee.',
            'final_answer_latex' => null,
        ])]]]]])]);
        $component->set('remainingGptLimit', 20)->call('askGpt')->assertSet('remainingGptLimit', 0);
        Http::assertNothingSent();
        $this->actor->update(['gpt_limit' => 1]);
        $this->ai($question->fresh())->call('askGpt')->assertSet('remainingGptLimit', 0);
        Http::assertSentCount(1);
        $this->assertSame(1, $this->actor->usageGptLimit());
        $this->assertSame($this->actor->id, AnswerAI::where('question_id', $question->id)->value('user_id'));
    }

    public function test_league_authorized_run_and_invalid_dates(): void
    {
        $this->actor->givePermissionTo(['dashboard:access', 'leagues:view', 'leagues:run']);
        settings()->set('leagueStatus', 'active');
        settings()->save();
        Livewire::test(LeagueRunForm::class)->set('is_live', '0')->set('start_at', 'not-a-date')->call('run')->assertHasErrors('start_at');
        Livewire::test(LeagueRunForm::class)->set('is_live', '0')->set('start_at', '2026-10-07 00:00')->set('end_at', '2026-10-06 23:59')->call('run')->assertHasErrors('end_at');
        Bus::assertNothingDispatched();
        CalculateLeague::mock()->shouldReceive('handle')->once()
            ->with('2026-10-01 00:00', '2026-10-07 23:59', false)->andReturn(new League);
        Livewire::test(LeagueRunForm::class)->set('is_live', '0')->set('start_at', '2026-10-01 00:00')->set('end_at', '2026-10-07 23:59')->call('run')->assertRedirect(route('admin.leagues.index'));
        Bus::assertNothingDispatched();
    }

    public function test_review_screen_uses_existing_scoped_table(): void
    {
        $this->actor->givePermissionTo(['dashboard:access', 'exams:view', 'exams-reviews:view']);
        $exam = Exam::factory()->create(['code' => fake()->uuid(), 'locale' => 'nl']);
        $this->get(route('admin.exams.review', $exam))->assertOk()
            ->assertSee('reviews-table')->assertSee('exam_id='.$exam->id, false);
    }

    public function test_favorite_and_solved_exam_counts_are_personal_and_relation_resolves(): void
    {
        $other = User::factory()->create();
        $exam = Exam::factory()->create(['code' => fake()->uuid(), 'locale' => 'nl']);
        $this->actor->favoriteExams()->attach($exam);
        foreach ([[$this->actor, 2, 1], [$this->actor, 3, 2], [$other, 50, 20]] as [$owner, $correct, $incorrect]) {
            $exam->results()->create(['user_id' => $owner->id, 'question_count' => $correct + $incorrect, 'correct_count' => $correct, 'incorrect_count' => $incorrect, 'completed' => 1, 'season_id' => 0, 'time' => 0]);
        }
        $favorite = Livewire::withoutLazyLoading()->test(FavoriteTests::class)->instance()->tests()->firstWhere('id', $exam->id);
        $this->assertSame(2, $favorite->personal_results_count);
        $this->assertEquals(5, $favorite->personal_correct_count);
        $this->assertEquals(3, $favorite->personal_incorrect_count);
        $test = Test::factory()->create(['code' => fake()->uuid(), 'locale' => 'nl']);
        foreach ([[$this->actor, 1], [$this->actor, 1], [$this->actor, 0], [$other, 1]] as [$owner, $completed]) {
            $result = TestsResult::create(['user_id' => $owner->id, 'test_id' => $test->id, 'question_count' => 1, 'correct_count' => 0, 'incorrect_count' => 0, 'completed' => $completed, 'season_id' => 0, 'duration' => 0, 'point' => 0, 'passing_score' => 0]);
        }
        $solved = Livewire::withoutLazyLoading()->test(SolvedExams::class)->instance()->exams()->firstWhere('id', $test->id);
        $this->assertSame(2, $solved->results_count);
        $detail = $result->details()->create(['question_id' => 0, 'answer_id' => 0, 'correct' => 0, 'point' => 0, 'section_id' => 0, 'lesson_id' => 0, 'topic_id' => 0]);
        $this->assertSame($result->id, $detail->testResult?->id);
    }
}
