<?php

namespace Tests\Feature;

use App\Actions\Leagues\CalculateLeague;
use App\Actions\Leagues\RecalculateClosedLeague;
use App\Console\Commands\RefreshLiveLeagues;
use App\Console\Commands\RunCalculateLeagueResult;
use App\Enums\YesNoEnum;
use App\Helpers\LeaguePeriod;
use App\Jobs\CalculateLeagueResult;
use App\Models\ExamResult;
use App\Models\League;
use App\Models\User;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LiveLeagueSchedulingTest extends TestCase
{
    use DatabaseTransactions;

    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
        config(['app.timezone' => 'Europe/Brussels']);
        date_default_timezone_set('Europe/Brussels');
        $this->clock('2026-10-25 12:00:00');
        settings()->set(['leagueStatus' => 'active', 'leagueResultModel' => 'App\\Models\\ExamResultDetail',
            'leagueCorrectPoint' => 3, 'leagueIncorrectPoint' => -1, 'leagueUserCount' => 10,
            'leagueDay' => 6, 'leagueHour' => '00:00', 'leagueStartDays' => 7, 'timezone' => 'Europe/Brussels']);
        settings()->save();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    private function clock(string $time): void
    {
        $this->travelTo(Carbon::parse($time, 'Europe/Brussels'));
    }

    private function live(string $start = '2026-10-24 00:00', string $end = '2026-10-30 23:59'): League
    {
        return CalculateLeague::run($start, $end, live: true);
    }

    private function answer(User $user, int $question = 1)
    {
        $attempt = ExamResult::create(['user_id' => $user->id, 'exam_id' => $question,
            'completed' => YesNoEnum::YES, 'question_count' => 1, 'season_id' => 1]);

        return $attempt->details()->create(['question_id' => $question, 'answer_id' => $question,
            'lesson_id' => 1, 'topic_id' => 1, 'correct' => YesNoEnum::YES, 'time' => 10]);
    }

    private function command(string $class = RefreshLiveLeagues::class): CommandTester
    {
        $command = new $class;
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $tester->execute([]);

        return $tester;
    }

    private function staff(): User
    {
        $actor = User::factory()->create();
        foreach (['dashboard:access', 'leagues:view', 'leagues:run'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $actor->givePermissionTo($permission);
        }
        $this->actingAs($actor);

        return $actor;
    }

    public function test_registered_schedule_uses_six_local_slots_and_overlap_protection(): void
    {
        $this->app->forgetInstance(Kernel::class);
        $this->app->make(Kernel::class)->all();
        $events = collect(app(Schedule::class)->events());
        $event = $events->firstWhere('description', 'refresh_live_leagues');
        $this->assertNotNull($event);
        $this->assertStringContainsString('leagues:refresh-live', $event->command);
        $this->assertSame('0 */4 * * *', $event->expression);
        $this->assertSame('Europe/Brussels', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(240, $event->expiresAt);
        $cron = new CronExpression($event->expression);
        foreach (range(0, 23) as $hour) {
            foreach ([0, 1] as $minute) {
                $date = Carbon::parse('2026-10-25', 'Europe/Brussels')->setTime($hour, $minute);
                $this->assertSame($minute === 0 && $hour % 4 === 0, $cron->isDue($date, 'Europe/Brussels'));
            }
        }
        $weekly = $events->firstWhere('description', 'run_league_result');
        $this->assertSame('0 * * * *', $weekly->expression);
        $this->assertSame('Europe/Brussels', $weekly->timezone);
        $this->assertTrue($weekly->withoutOverlapping);
    }

    public function test_refresh_updates_running_league_without_changing_identity_or_end(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        $this->clock('2026-10-25 16:00:00');
        $this->answer($user, 2);
        $this->assertSame(0, $this->command()->getStatusCode());
        $fresh = $league->fresh();
        $this->assertSame($league->code, $fresh->code);
        $this->assertSame(6, $fresh->results()->firstOrFail()->total_score);
        $this->assertEquals(now(), $fresh->calculated_through_at);
        $this->assertEquals($league->end_at, $fresh->end_at);
        $this->assertNull($fresh->closed_at);
    }

    public function test_future_period_is_not_calculated_by_command_or_stale_scheduled_call(): void
    {
        $league = $this->live('2026-11-07', '2026-11-13 23:59');
        $this->assertSame(0, $this->command()->getStatusCode());
        $this->assertNull(CalculateLeague::run('', '', leagueId: $league->id, scheduled: true));
        $this->assertNull($league->fresh()->calculated_at);
        $this->assertNull($league->fresh()->closed_at);
    }

    public function test_overdue_empty_league_is_finalized_at_its_original_data_boundary(): void
    {
        $league = $this->live();
        $this->clock('2026-11-02 04:00:00');
        $this->assertSame(0, $this->command()->getStatusCode());
        $fresh = $league->fresh();
        $this->assertEquals($league->end_at, $fresh->calculated_through_at);
        $this->assertEquals(now(), $fresh->calculated_at);
        $this->assertEquals(now(), $fresh->closed_at);
        $this->assertSame(0, $fresh->results()->count());
    }

    public function test_closed_league_is_skipped_including_a_stale_candidate_call(): void
    {
        $league = $this->live();
        $this->clock('2026-11-02 04:00:00');
        $this->command();
        $closed = $league->fresh();
        $this->clock('2026-11-02 08:00:00');
        $command = $this->command();
        $this->assertSame(0, $command->getStatusCode());
        $this->assertStringContainsString('Updated: 0', $command->getDisplay());
        $this->assertNull(CalculateLeague::run('', '', leagueId: $league->id, scheduled: true));
        $this->assertEquals($closed->calculated_at, $league->fresh()->calculated_at);
        $this->assertEquals($closed->closed_at, $league->fresh()->closed_at);
    }

    public function test_period_is_not_closed_at_its_last_second_but_is_closed_at_next_midnight(): void
    {
        $league = $this->live();
        $this->clock('2026-10-30 23:59:59');
        $this->command();
        $this->assertNull($league->fresh()->closed_at);
        $this->clock('2026-10-31 00:00:00');
        $this->command();
        $this->assertEquals(now(), $league->fresh()->closed_at);
        $this->assertEquals($league->end_at, $league->fresh()->calculated_through_at);
    }

    public function test_calculation_crossing_midnight_does_not_finalize_a_partial_data_window(): void
    {
        $league = $this->live();
        $this->clock('2026-10-30 23:59:50');
        $advance = true;
        DB::listen(function (QueryExecuted $query) use (&$advance) {
            if ($advance && str_contains($query->sql, 'RANK() OVER')) {
                $advance = false;
                $this->clock('2026-10-31 00:00:05');
            }
        });
        try {
            $this->command();
        } finally {
            $advance = false;
        }
        $this->assertNull($league->fresh()->closed_at);
        $this->assertSame('2026-10-30 23:59:50', $league->fresh()->calculated_through_at->format('Y-m-d H:i:s'));
        $this->clock('2026-10-31 04:00:00');
        $this->command();
        $this->assertNotNull($league->fresh()->closed_at);
        $this->assertEquals($league->end_at, $league->fresh()->calculated_through_at);
    }

    public function test_failure_in_one_league_does_not_prevent_another_leagues_finalization(): void
    {
        $bad = $this->live();
        $good = $this->live('2026-10-23', '2026-10-30 23:59');
        $bad->update(['calculation_settings' => null]);
        $this->clock('2026-11-02 04:00:00');
        Log::spy();
        $this->assertSame(1, $this->command()->getStatusCode());
        $this->assertNull($bad->fresh()->closed_at);
        $this->assertEquals($bad->calculated_at, $bad->fresh()->calculated_at);
        $this->assertNotNull($good->fresh()->closed_at);
        Log::shouldHaveReceived('error')->once()->with('Live league calculation failed.', ['league_id' => $bad->id, 'exception' => InvalidArgumentException::class]);
    }

    public function test_failed_final_metadata_write_rolls_back_and_next_command_can_finalize(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live();
        $this->answer($user, 2);
        $this->clock('2026-11-02 04:00:00');
        $interrupt = true;
        DB::listen(function (QueryExecuted $query) use (&$interrupt) {
            if ($interrupt && str_starts_with($query->sql, 'update `leagues`') && str_contains($query->sql, '`closed_at`')) {
                $interrupt = false;
                throw new RuntimeException('Synthetic final metadata failure');
            }
        });
        Log::spy();
        try {
            $this->assertSame(1, $this->command()->getStatusCode());
        } finally {
            $interrupt = false;
        }
        $this->assertNull($league->fresh()->closed_at);
        $this->assertSame(3, $league->results()->firstOrFail()->total_score);
        $this->assertEquals($league->calculated_at, $league->fresh()->calculated_at);
        $this->clock('2026-11-02 08:00:00');
        $this->assertSame(0, $this->command()->getStatusCode());
        $this->assertNotNull($league->fresh()->closed_at);
        $this->assertSame(6, $league->results()->firstOrFail()->total_score);
    }

    public function test_busy_period_is_skipped_without_error_and_other_period_can_finish(): void
    {
        $busy = $this->live();
        $other = $this->live('2026-10-23', '2026-10-30 23:59');
        $this->clock('2026-11-02 04:00:00');
        $config = config('database.connections.'.config('database.default'));
        config(['database.connections.league_busy_test' => $config]);
        $connection = DB::connection('league_busy_test');
        $period = LeaguePeriod::fromStrings('2026-10-24', '2026-10-30 23:59');
        $key = 'league:'.sha1(DB::connection()->getDatabaseName().'|'.$period->lockKey('exam_result_details'));
        Log::spy();
        try {
            $this->assertSame(1, (int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$key])->acquired);
            $command = $this->command();
            $this->assertSame(0, $command->getStatusCode());
            $this->assertStringContainsString('skipped: 1', $command->getDisplay());
            $this->assertNull($busy->fresh()->closed_at);
            $this->assertNotNull($other->fresh()->closed_at);
            Log::shouldNotHaveReceived('error');
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$key]);
            DB::purge('league_busy_test');
            config(['database.connections.league_busy_test' => null]);
        }
        $this->command();
        $this->assertNotNull($busy->fresh()->closed_at);
    }

    public function test_passive_mode_skips_both_commands_and_resuming_finishes_overdue_league(): void
    {
        $league = $this->live();
        settings()->set('leagueStatus', 'passive');
        settings()->save();
        $this->clock('2026-11-02 04:00:00');
        $this->assertSame(0, $this->command()->getStatusCode());
        $this->assertSame(0, $this->command(RunCalculateLeagueResult::class)->getStatusCode());
        $this->assertNull($league->fresh()->closed_at);
        settings()->set('leagueStatus', 'active');
        settings()->save();
        $this->command();
        $this->assertNotNull($league->fresh()->closed_at);
    }

    public function test_weekly_command_keeps_its_configured_date_range_and_skips_not_due_calls(): void
    {
        Bus::fake([CalculateLeagueResult::class]);
        $this->assertSame(0, $this->command(RunCalculateLeagueResult::class)->getStatusCode());
        Bus::assertNothingDispatched();
        $this->clock('2026-10-24 01:00:00');
        $this->assertSame(0, $this->command(RunCalculateLeagueResult::class)->getStatusCode());
        Bus::assertNothingDispatched();
        $this->clock('2026-10-24 00:00:00');
        $this->assertSame(0, $this->command(RunCalculateLeagueResult::class)->getStatusCode());
        Bus::assertDispatchedSync(CalculateLeagueResult::class, fn ($job) => $job->started_at === '2026-10-17 00:00:00' && $job->ended_at === '2026-10-24 00:00:59');
    }

    public function test_weekly_day_and_hour_gate_use_the_site_timezone(): void
    {
        Bus::fake([CalculateLeagueResult::class]);
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
        $this->travelTo(Carbon::parse('2026-10-23 23:00:00', 'UTC'));
        settings()->set('leagueHour', '01:00');
        settings()->save();
        $this->assertSame(0, $this->command(RunCalculateLeagueResult::class)->getStatusCode());
        Bus::assertDispatchedSync(CalculateLeagueResult::class, fn ($job) => $job->started_at === '2026-10-17 01:00:00' && $job->ended_at === '2026-10-24 01:00:59');
    }

    public function test_real_weekly_command_updates_and_closes_the_matching_live_league(): void
    {
        $this->clock('2026-10-24 00:00:00');
        $user = User::factory()->create(['league_approval' => 1]);
        $this->answer($user);
        $league = $this->live('2026-10-17', '2026-10-24 00:00');
        $this->answer($user, 2)->forceFill(['updated_at' => '2026-10-24 00:00:30'])->save();
        $count = League::count();
        settings()->set('leagueCorrectPoint', 9);
        settings()->save();
        $this->clock('2026-10-24 00:01:00');
        $this->assertSame(0, $this->command(RunCalculateLeagueResult::class)->getStatusCode());
        $this->assertSame($count, League::count());
        $this->assertSame(6, $league->results()->firstOrFail()->total_score);
        $this->assertEquals($league->end_at, $league->fresh()->calculated_through_at);
        $this->assertNotNull($league->fresh()->closed_at);
    }

    public function test_authorized_manual_recalculation_keeps_closed_state_and_frozen_points(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $answer = $this->answer($user);
        $league = $this->live();
        $this->clock('2026-11-02 04:00:00');
        $this->command();
        $closed = $league->fresh()->closed_at;
        $answer::withoutTimestamps(fn () => $answer->forceFill(['correct' => YesNoEnum::NO, 'updated_at' => '2026-10-25 12:00:00'])->save());
        settings()->set('leagueIncorrectPoint', 9);
        settings()->save();
        $this->staff();
        $this->clock('2026-11-02 05:00:00');
        $result = RecalculateClosedLeague::run($league->id);
        $this->assertSame(-1, $result->results()->firstOrFail()->total_score);
        $this->assertEquals($closed, $result->closed_at);
        $this->assertEquals($league->end_at, $result->calculated_through_at);
        $this->assertEquals(now(), $result->calculated_at);
    }

    public function test_manual_recalculation_respects_answers_moved_outside_the_original_period(): void
    {
        $user = User::factory()->create(['league_approval' => 1]);
        $answer = $this->answer($user);
        $league = $this->live();
        $this->clock('2026-11-02 04:00:00');
        $this->command();
        $closed = $league->fresh()->closed_at;
        $answer->update(['correct' => YesNoEnum::NO]);
        $this->staff();
        $result = RecalculateClosedLeague::run($league->id);
        $this->assertSame(0, $result->results()->count());
        $this->assertEquals($closed, $result->closed_at);
        $this->assertEquals($league->end_at, $result->calculated_through_at);
    }

    public function test_busy_weekly_command_skips_without_error_and_can_finalize_after_release(): void
    {
        $this->clock('2026-10-24 00:00:00');
        $league = $this->live('2026-10-17', '2026-10-24 00:00');
        $this->clock('2026-10-24 00:01:00');
        config(['database.connections.weekly_busy_test' => config('database.connections.'.config('database.default'))]);
        $connection = DB::connection('weekly_busy_test');
        $period = LeaguePeriod::fromStrings('2026-10-17', '2026-10-24 00:00');
        $key = 'league:'.sha1(DB::connection()->getDatabaseName().'|'.$period->lockKey('exam_result_details'));
        try {
            $this->assertSame(1, (int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$key])->acquired);
            $command = $this->command(RunCalculateLeagueResult::class);
            $this->assertSame(0, $command->getStatusCode());
            $this->assertStringContainsString('already running', $command->getDisplay());
            $this->assertNull($league->fresh()->closed_at);
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$key]);
            DB::purge('weekly_busy_test');
            config(['database.connections.weekly_busy_test' => null]);
        }
        $this->command(RunCalculateLeagueResult::class);
        $this->assertNotNull($league->fresh()->closed_at);
    }

    public function test_manual_recalculation_rechecks_permission_and_rejects_an_open_league(): void
    {
        $league = $this->live();
        $actor = $this->staff();
        try {
            RecalculateClosedLeague::run($league->id);
            $this->fail('Open league was accepted.');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $actor->revokePermissionTo('leagues:run');
        try {
            RecalculateClosedLeague::run($league->id);
            $this->fail('Revoked permission was accepted.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_manual_recalculation_requires_login(): void
    {
        try {
            RecalculateClosedLeague::run(1);
            $this->fail('Guest recalculation was accepted.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_scheduled_entry_cannot_create_a_league_or_calculate_a_single_snapshot(): void
    {
        $this->staff();
        $single = League::create(['code' => 'phase2-single', 'label' => 'exam_result_details', 'start_at' => '2026-10-24', 'end_at' => '2026-10-30 23:59:59']);
        $this->assertNull(CalculateLeague::run('', '', leagueId: $single->id, scheduled: true));
        try {
            RecalculateClosedLeague::run($single->id);
            $this->fail('Single calculation was accepted.');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->expectException(InvalidArgumentException::class);
        CalculateLeague::run('2026-10-24', '2026-10-30', scheduled: true);
    }

    public function test_chunked_command_processes_more_than_fifty_leagues_without_skipping_rows(): void
    {
        $codes = [];
        foreach (range(1, 51) as $i) {
            $codes[] = 'phase2-batch-'.$i;
            DB::table('leagues')->insert(['code' => end($codes), 'label' => 'exam_result_details',
                'start_at' => Carbon::parse('2026-10-24')->addMinutes($i), 'end_at' => '2026-10-30 23:59:59',
                'is_live' => true, 'calculation_settings' => json_encode(['correct_point' => 3, 'incorrect_point' => -1, 'user_count' => 10])]);
        }
        $this->clock('2026-11-02 04:00:00');
        $command = $this->command();
        $this->assertSame(0, $command->getStatusCode());
        $this->assertStringContainsString('Updated: 51; closed: 51', $command->getDisplay());
        $this->assertSame(51, League::whereIn('code', $codes)->whereNotNull('closed_at')->count());
    }
}
