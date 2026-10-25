<?php

namespace Tests\Unit;

use App\Helpers\LeaguePeriod;
use App\Jobs\CalculateLeagueResult;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LeaguePeriodTest extends TestCase
{
    public static function weekdays(): array
    {
        return array_map(fn ($day) => [$day], range(24, 30));
    }

    #[DataProvider('weekdays')]
    public function test_defaults_are_the_current_saturday_through_friday(int $day): void
    {
        $period = LeaguePeriod::currentWeek(CarbonImmutable::parse('2026-10-'.$day.' 12:00', 'Europe/Brussels'));
        $this->assertSame('2026-10-24 00:00:00', $period->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-30 23:59:59', $period->end->format('Y-m-d H:i:s'));
        $this->assertEquals(7, $period->start->diffInDays($period->end->addSecond()));
    }

    public function test_next_saturday_opens_a_new_default_period(): void
    {
        $period = LeaguePeriod::currentWeek(CarbonImmutable::parse('2026-10-31 00:00', 'Europe/Brussels'));
        $this->assertSame('2026-10-31 00:00:00', $period->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-06 23:59:59', $period->end->format('Y-m-d H:i:s'));
    }

    public static function clockChanges(): array
    {
        return [['2026-03-28', 167], ['2026-10-24', 169], ['2026-12-31', 168], ['2028-02-29', 168]];
    }

    #[DataProvider('clockChanges')]
    public function test_calendar_week_handles_clock_year_and_leap_boundaries(string $date, int $hours): void
    {
        $period = LeaguePeriod::currentWeek(CarbonImmutable::parse($date, 'Europe/Brussels'));
        $this->assertSame(6, $period->start->dayOfWeek);
        $this->assertSame(5, $period->end->dayOfWeek);
        $this->assertEquals($hours, $period->start->diffInHours($period->end->addSecond()));
    }

    public function test_custom_periods_are_free_and_keep_the_existing_minute_precision(): void
    {
        $job = new CalculateLeagueResult('2026-10-20 13:07:12', '2026-11-02 17:43:14');
        $this->assertSame('2026-10-20 13:07:00', $job->started_at);
        $this->assertSame('2026-11-02 17:43:59', $job->ended_at);
        $period = LeaguePeriod::fromStrings($job->started_at, $job->ended_at);
        $this->assertSame($job->ended_at, $period->end->format('Y-m-d H:i:s'));
    }

    public function test_cutoff_excludes_future_and_caps_late_calculation_at_end(): void
    {
        $period = LeaguePeriod::currentWeek(CarbonImmutable::parse('2026-10-24', 'UTC'));
        $this->assertNull($period->cutoff($period->start->subSecond()));
        $this->assertEquals($period->start, $period->cutoff($period->start));
        $now = CarbonImmutable::parse('2026-10-25 04:00:12', 'UTC');
        $this->assertEquals($now, $period->cutoff($now));
        $this->assertEquals($period->end, $period->cutoff($period->end->addHours(4)));
    }

    public function test_same_period_has_a_stable_source_specific_lock_key(): void
    {
        $a = LeaguePeriod::fromStrings('2026-10-24 00:00', '2026-10-30 23:59');
        $b = LeaguePeriod::fromStrings('2026-10-24 00:00:00', '2026-10-30 23:59:59');
        $this->assertSame($a->lockKey('exam_result_details'), $b->lockKey('exam_result_details'));
        $this->assertNotSame($a->lockKey('exam_result_details'), $a->lockKey('tests_result_details'));
        $this->assertLessThanOrEqual(64, strlen($a->lockKey('exam_result_details')));
    }

    public function test_reversed_period_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LeaguePeriod::fromStrings('2026-10-31 00:00', '2026-10-30 23:59');
    }
}
