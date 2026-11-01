<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class StudyCalendar
{
    public static function day(string|DateTimeInterface $value, ?string $timezone = null): CarbonImmutable
    {
        $timezone ??= config('app.timezone');
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone($timezone)->startOfDay();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            throw new InvalidArgumentException('Study dates must use YYYY-MM-DD.');
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Invalid study date.');
        }

        return $date;
    }

    /** Study dates are calendar days, not timestamps to convert between timezones. */
    public static function label(string|DateTimeInterface $value, string $format = 'j F Y'): string
    {
        $date = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;

        return self::day($date)->locale(app()->getLocale())->translatedFormat($format);
    }

    /** The upper boundary belongs to the next week. Calendar days preserve DST. */
    public static function bounds(string|DateTimeInterface $value, ?string $timezone = null): array
    {
        $day = self::day($value, $timezone);
        $start = $day->subDays(($day->dayOfWeek + 1) % 7);

        return ['start' => $start, 'end' => $start->addWeek()];
    }

    /** Full weeks can appear in both adjacent seasons, with the same identity. */
    public static function weeksBetween(string|DateTimeInterface $from, string|DateTimeInterface $to, ?string $timezone = null): array
    {
        $first = self::day($from, $timezone);
        $last = self::day($to, $timezone);
        if ($last->lessThan($first)) {
            throw new InvalidArgumentException('Study period end precedes start.');
        }
        $weeks = [];
        for ($start = self::bounds($first, $timezone)['start']; $start->lessThanOrEqualTo($last); $start = $start->addWeek()) {
            $weeks[] = ['start' => $start, 'end' => $start->addWeek()];
        }

        return $weeks;
    }

    /** Overlapping periods share full calendar weeks; gaps do not create extra weeks. */
    public static function weeksForPeriods(iterable $periods, ?string $timezone = null): array
    {
        $weeks = [];
        foreach ($periods as $period) {
            foreach (self::weeksBetween($period->start_date, $period->end_date, $timezone) as $bounds) {
                $weeks[$bounds['start']->toDateString()] = $bounds;
            }
        }
        ksort($weeks);

        return array_values($weeks);
    }
}
