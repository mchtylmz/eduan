<?php

namespace Tests\Feature;

use App\Actions\Leagues\CalculateLeague;
use App\Enums\YesNoEnum;
use App\Helpers\LeaguePeriod;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\League;
use App\Models\TestsResultDetail;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/** Requires the approved live-league migration on the isolated testing database. */
class LiveLeagueCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 25)->setTime(12, 0));
        settings()->set(['leagueStatus' => 'active', 'leagueResultModel' => ExamResultDetail::class,
            'leagueUserCount' => 1, 'leagueCorrectPoint' => 3, 'leagueIncorrectPoint' => -1]);
        settings()->save();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function answer(User $user, int $question = 1): ExamResultDetail
    {
        $result = ExamResult::create(['user_id' => $user->id, 'exam_id' => $question,
            'completed' => YesNoEnum::YES, 'question_count' => 1, 'season_id' => 1]);

        return $result->details()->create(['question_id' => $question, 'answer_id' => $question,
            'lesson_id' => 1, 'topic_id' => 1, 'correct' => YesNoEnum::YES, 'time' => 10]);
    }

    private function live(): League
    {
        return CalculateLeague::run('2026-10-24 00:00', '2026-10-30 23:59', live: true);
    }

    public function test_repeated_live_creation_keeps_one_identity_and_frozen_rules(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        settings()->set(['leagueCorrectPoint' => 9, 'leagueUserCount' => 20]);
        settings()->save();
        $again = $this->live();
        $this->assertSame($league->id, $again->id);
        $this->assertSame($league->code, $again->code);
        $this->assertSame(['correct_point' => 3, 'incorrect_point' => -1, 'user_count' => 1], $again->calculation_settings);
        $this->assertSame(3, $again->results()->firstOrFail()->total_score);
        $this->assertSame('2026-10-30 23:59:59', $again->end_at->format('Y-m-d H:i:s'));
        $this->assertNull($again->closed_at);
    }

    public function test_legacy_entry_point_recalculates_the_matching_live_record(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        $this->answer($user, 2);
        settings()->set('leagueCorrectPoint', 8);
        settings()->save();
        $again = CalculateLeague::run('2026-10-24 00:00', '2026-10-30 23:59');
        $this->assertSame($league->id, $again->id);
        $this->assertSame(6, $again->results()->firstOrFail()->total_score);
        $this->assertSame(1, League::where('code', $league->code)->count());
    }

    public function test_id_based_recalculation_uses_the_frozen_source_after_global_source_changes(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        settings()->set('leagueResultModel', TestsResultDetail::class);
        settings()->save();
        $again = CalculateLeague::run('2026-01-01', '2026-01-02', leagueId: $league->id);
        $this->assertSame('exam_result_details', $again->label);
        $this->assertSame(3, $again->results()->firstOrFail()->total_score);
        $this->assertEquals($league->start_at, $again->start_at);
        $this->assertEquals($league->end_at, $again->end_at);
    }

    public function test_future_live_period_is_saved_without_claiming_a_calculation(): void
    {
        $league = CalculateLeague::run('2026-11-07 00:00', '2026-11-13 23:59', live: true);
        $this->assertTrue($league->is_live);
        $this->assertNull($league->calculated_at);
        $this->assertNull($league->calculated_through_at);
        $this->assertNull($league->closed_at);
        $this->assertSame(0, $league->results()->count());
    }

    public function test_started_empty_live_period_is_valid_but_empty_single_calculation_is_not_created(): void
    {
        $count = League::count();
        $this->assertNull(CalculateLeague::run('2026-10-24 00:00', '2026-10-30 23:59'));
        $this->assertSame($count, League::count());
        $league = $this->live();
        $this->assertSame($count + 1, League::count());
        $this->assertEquals(now(), $league->calculated_through_at);
        $this->assertNotNull($league->calculated_at);
        $this->assertSame(0, $league->results()->count());
    }

    public function test_live_refresh_removes_only_stale_results_in_that_league(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        $other = CalculateLeague::run('2026-10-23 00:00', '2026-10-31 23:59', live: true);
        $user->update(['league_approval' => 0]);
        $this->live();
        $this->assertSame(0, $league->results()->count());
        $this->assertSame(1, $other->results()->count());
        $this->assertModelExists($league);
        $this->assertSame(1, ExamResultDetail::where('exam_result_id', ExamResult::where('user_id', $user->id)->firstOrFail()->id)->count());
    }

    public function test_running_cutoff_excludes_future_answers_and_late_calculation_stops_at_end(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $this->answer($user, 2)->forceFill(['updated_at' => now()->addHour()])->save();
        $league = $this->live();
        $this->assertSame(3, $league->results()->firstOrFail()->total_score);
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(4, 0));
        $again = $this->live();
        $this->assertSame(6, $again->results()->firstOrFail()->total_score);
        $this->assertEquals($again->end_at, $again->calculated_through_at);
        $this->assertEquals(now(), $again->calculated_at);
    }

    public function test_historical_dates_and_closed_marker_are_not_rewritten(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = CalculateLeague::run('2026-10-16 14:32', '2026-10-26 20:41', live: true);
        $this->travelTo(now()->setDate(2026, 10, 27)->setTime(4, 0));
        $closed = now()->subHour();
        $league->update(['closed_at' => $closed]);
        $again = CalculateLeague::run('', '', leagueId: $league->id);
        $this->assertSame('2026-10-16 14:32:00', $again->start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-26 20:41:59', $again->end_at->format('Y-m-d H:i:s'));
        $this->assertEquals($closed, $again->closed_at);
    }

    public function test_explicit_live_creation_does_not_convert_an_existing_single_calculation(): void
    {
        $this->answer(User::factory()->create(['league_approval' => 1]));
        $single = CalculateLeague::run('2026-10-24', '2026-10-30 23:59');
        try {
            $this->live();
            $this->fail('Historical single calculation was converted.');
        } catch (InvalidArgumentException) {
            $this->assertFalse($single->fresh()->is_live);
            $this->assertNull($single->fresh()->calculation_settings);
        }
    }

    public function test_invalid_snapshot_fails_without_replacing_last_successful_results(): void
    {
        $this->answer(User::factory()->create(['league_approval' => 1]));
        $league = $this->live();
        $league->update(['calculation_settings' => null]);
        try {
            $this->live();
            $this->fail('Invalid snapshot was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame(3, $league->results()->firstOrFail()->total_score);
            $this->assertEquals($league->calculated_at, $league->fresh()->calculated_at);
        }
    }

    public function test_failure_after_upsert_rolls_back_results_and_releases_the_period_lock(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        $this->answer($user, 2);
        $interrupt = true;
        DB::listen(function (QueryExecuted $query) use (&$interrupt) {
            if ($interrupt && str_starts_with(strtolower($query->sql), 'delete from `league_results`')) {
                $interrupt = false;
                throw new RuntimeException('Synthetic write failure');
            }
        });
        try {
            $this->live();
            $this->fail('Expected synthetic failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic write failure', $e->getMessage());
            $this->assertSame(3, $league->results()->firstOrFail()->total_score);
            $this->assertEquals($league->calculated_at, $league->fresh()->calculated_at);
        } finally {
            $interrupt = false;
        }
        $this->assertSame(6, $this->live()->results()->firstOrFail()->total_score);
    }

    public function test_another_connection_cannot_calculate_the_same_period_concurrently(): void
    {
        $connectionName = config('database.default');
        config(['database.connections.league_lock_test' => config('database.connections.'.$connectionName)]);
        $other = DB::connection('league_lock_test');
        $period = LeaguePeriod::fromStrings('2026-10-24 00:00', '2026-10-30 23:59');
        $key = 'league:'.sha1(DB::connection()->getDatabaseName().'|'.$period->lockKey('exam_result_details'));
        try {
            $this->assertSame(1, (int) $other->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$key])->acquired);
            try {
                $this->live();
                $this->fail('The period lock was bypassed.');
            } catch (RuntimeException $e) {
                $this->assertSame('League calculation is already running.', $e->getMessage());
            }
        } finally {
            $other->selectOne('SELECT RELEASE_LOCK(?) AS released', [$key]);
            DB::purge('league_lock_test');
            config(['database.connections.league_lock_test' => null]);
        }
        $this->assertTrue($this->live()->is_live);
    }

    public function test_missing_historical_dates_are_rejected_without_creating_results(): void
    {
        $league = League::create(['code' => 'phase1-nodate', 'label' => 'exam_result_details']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('League dates are missing.');
        CalculateLeague::run('', '', leagueId: $league->id);
    }

    public function test_passive_system_does_not_change_existing_live_results(): void
    {
        $this->answer(User::factory()->create(['league_approval' => 1]));
        $league = $this->live();
        settings()->set('leagueStatus', 'passive');
        settings()->save();
        try {
            $this->live();
            $this->fail('Passive league was recalculated.');
        } catch (RuntimeException) {
            $this->assertSame(3, $league->results()->firstOrFail()->total_score);
            $this->assertEquals($league->calculated_at, $league->fresh()->calculated_at);
        }
    }
}
