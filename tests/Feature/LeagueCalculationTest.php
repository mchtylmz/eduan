<?php

namespace Tests\Feature;

use App\Enums\YesNoEnum;
use App\Jobs\CalculateLeagueResult;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\TestsResult;
use App\Models\TestsResultDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LeagueCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfMinute());
        settings()->set([
            'leagueStatus' => 'active', 'leagueResultModel' => ExamResultDetail::class,
            'leagueUserCount' => 1, 'leagueCorrectPoint' => 3, 'leagueIncorrectPoint' => -1,
        ]);
        settings()->save();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function createResult(User $user, int $questionId, YesNoEnum $correct): ExamResultDetail
    {
        $result = ExamResult::create([
            'user_id' => $user->id, 'exam_id' => $questionId,
            'completed' => YesNoEnum::YES, 'question_count' => 1, 'season_id' => 1,
        ]);

        return $result->details()->create([
            'question_id' => $questionId, 'answer_id' => $questionId,
            'lesson_id' => 1, 'topic_id' => 1, 'correct' => $correct, 'time' => 10,
        ]);
    }

    private function calculate(): void
    {
        (new CalculateLeagueResult(now()->subHour()->toDateTimeString(), now()->addMinute()->toDateTimeString()))->handle();
    }

    public function test_wrong_answers_use_the_signed_setting_and_recalculation_updates_league_number(): void
    {
        $first = User::factory()->create(['league_approval' => 1]);
        $second = User::factory()->create(['league_approval' => 1]);
        $this->createResult($first, 1, YesNoEnum::YES);
        $wrong = $this->createResult($second, 2, YesNoEnum::NO);
        $this->calculate();
        $this->assertSame(-1, LeagueResult::where('user_id', $second->id)->first()->total_score);
        $this->assertSame(2, LeagueResult::where('user_id', $second->id)->first()->league_number);
        $wrong->update(['correct' => YesNoEnum::YES]);
        $this->calculate();
        $this->assertSame(1, LeagueResult::where('user_id', $second->id)->first()->league_number);
    }

    public function test_no_results_does_not_create_an_empty_league(): void
    {
        $leagues = League::count();
        $results = LeagueResult::count();
        $this->calculate();
        $this->assertSame($leagues, League::count());
        $this->assertSame($results, LeagueResult::count());
    }

    public function test_practice_repeated_and_unfinished_attempts_all_contribute_saved_answers(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->createResult($user, 1, YesNoEnum::YES);
        $unfinished = $this->createResult($user, 1, YesNoEnum::YES);
        $unfinished->examResult()->first()->update(['completed' => YesNoEnum::NO]);
        $this->createResult($user, 1, YesNoEnum::NO);
        $outsidePeriod = $this->createResult($user, 1, YesNoEnum::YES);
        $outsidePeriod->forceFill(['updated_at' => now()->subHours(2)])->save();

        $this->calculate();
        $row = LeagueResult::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(5, $row->total_score);
        $this->assertSame(2, $row->total_correct);
        $this->assertSame(1, $row->total_incorrect);
        $this->assertSame(4, ExamResult::where('user_id', $user->id)->count());
    }

    public function test_timed_repeated_completed_attempts_accumulate_but_unfinished_attempt_does_not(): void
    {
        settings()->set('leagueResultModel', TestsResultDetail::class);
        settings()->save();
        $user = User::factory()->create(['league_approval' => 1]);
        foreach ([YesNoEnum::YES, YesNoEnum::YES, YesNoEnum::NO] as $completed) {
            $result = TestsResult::create(['user_id' => $user->id, 'test_id' => 1, 'completed' => $completed, 'question_count' => 1]);
            $result->details()->create(['question_id' => 1, 'answer_id' => 1, 'lesson_id' => 1, 'topic_id' => 1, 'correct' => YesNoEnum::YES, 'time' => 10]);
        }
        $this->calculate();
        $row = LeagueResult::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(6, $row->total_score);
        $this->assertSame(2, $row->total_correct);
        $this->assertSame(20, $row->total_duration);
    }

    public function test_existing_ranking_uses_correct_count_then_duration_and_shared_rank(): void
    {
        $first = User::factory()->create(['league_approval' => 1]);
        $tied = User::factory()->create(['league_approval' => 1]);
        $slower = User::factory()->create(['league_approval' => 1]);
        $moreCorrect = User::factory()->create(['league_approval' => 1]);
        $this->createResult($first, 1, YesNoEnum::YES);
        $this->createResult($tied, 1, YesNoEnum::YES);
        $this->createResult($slower, 1, YesNoEnum::YES)->update(['time' => 20]);
        foreach ([YesNoEnum::YES, YesNoEnum::YES, YesNoEnum::NO, YesNoEnum::NO, YesNoEnum::NO] as $correct) {
            $this->createResult($moreCorrect, 1, $correct)->update(['time' => 100]);
        }
        $this->calculate();
        $ranks = LeagueResult::pluck('rank_position', 'user_id');
        $this->assertSame(1, $ranks[$moreCorrect->id]);
        $this->assertSame(2, $ranks[$first->id]);
        $this->assertSame(2, $ranks[$tied->id]);
        $this->assertSame(4, $ranks[$slower->id]);
    }
}
