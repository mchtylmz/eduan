<?php

namespace Tests\Unit;

use App\Helpers\StudyCalendar;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudyCalendarTest extends TestCase
{
    public static function weekdays(): array
    {
        return array_map(fn ($day) => [$day], range(10, 16));
    }

    #[DataProvider('weekdays')]
    public function test_every_day_has_the_same_full_saturday_week(int $day): void
    {
        $bounds = StudyCalendar::bounds('2026-10-'.$day, 'Europe/Brussels');
        $this->assertSame('2026-10-10 00:00:00', $bounds['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-17 00:00:00', $bounds['end']->format('Y-m-d H:i:s'));
    }

    public function test_midnight_boundary_and_utc_input_use_the_local_date(): void
    {
        $before = CarbonImmutable::parse('2026-10-16 23:59:59', 'Europe/Brussels');
        $after = $before->addSecond();
        $this->assertSame('2026-10-10', StudyCalendar::bounds($before, 'Europe/Brussels')['start']->toDateString());
        $this->assertSame('2026-10-17', StudyCalendar::bounds($after, 'Europe/Brussels')['start']->toDateString());
        $this->assertSame('2026-10-17', StudyCalendar::bounds($after->setTimezone('UTC'), 'Europe/Brussels')['start']->toDateString());
    }

    public static function clockChanges(): array
    {
        return [['2026-03-28', 167], ['2026-10-24', 169], ['2026-10-10', 168]];
    }

    #[DataProvider('clockChanges')]
    public function test_week_is_seven_calendar_days_even_at_clock_changes(string $day, int $hours): void
    {
        $bounds = StudyCalendar::bounds($day, 'Europe/Brussels');
        $this->assertSame(6, $bounds['end']->dayOfWeek);
        $this->assertSame('00:00:00', $bounds['end']->format('H:i:s'));
        $this->assertEquals($hours, $bounds['start']->diffInHours($bounds['end']));
    }

    public function test_adjacent_seasons_share_one_full_week_and_do_not_clip_it(): void
    {
        $a = StudyCalendar::weeksBetween('2026-10-01', '2026-10-14', 'UTC');
        $b = StudyCalendar::weeksBetween('2026-10-15', '2026-10-31', 'UTC');
        $this->assertSame('2026-09-26', $a[0]['start']->toDateString());
        $this->assertSame('2026-10-10', end($a)['start']->toDateString());
        $this->assertSame(end($a)['start']->toDateString(), $b[0]['start']->toDateString());
        $this->assertSame('2026-11-07', end($b)['end']->toDateString());
    }

    public function test_single_day_year_boundary_and_leap_day_are_full_weeks(): void
    {
        foreach (['2026-12-31', '2028-02-29'] as $date) {
            $weeks = StudyCalendar::weeksBetween($date, $date, 'UTC');
            $this->assertCount(1, $weeks);
            $this->assertSame(6, $weeks[0]['start']->dayOfWeek);
            $this->assertEquals(7, $weeks[0]['start']->diffInDays($weeks[0]['end']));
        }
    }

    public static function invalidDates(): array
    {
        return [['2026-02-30'], ['2026-13-01'], ['tomorrow'], ['2026-1-01'], ['']];
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_or_relative_dates_are_rejected(string $date): void
    {
        $this->expectException(InvalidArgumentException::class);
        StudyCalendar::bounds($date, 'UTC');
    }

    public function test_reversed_period_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StudyCalendar::weeksBetween('2026-10-17', '2026-10-16', 'UTC');
    }
}
