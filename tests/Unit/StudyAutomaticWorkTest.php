<?php

namespace Tests\Unit;

use App\Helpers\StudyAnswerClock;
use App\Helpers\StudyCalendar;
use App\Helpers\StudyDailyTotals;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudyAutomaticWorkTest extends TestCase
{
    public static function answerChanges(): array
    {
        return [[0, 10, null, '2026-10-17 00:01:00'], [10, 11, '2026-10-16 23:59:00', '2026-10-17 00:01:00'],
            [10, 10, '2026-10-16 23:59:00', '2026-10-16 23:59:00'], [10, 0, '2026-10-16 23:59:00', null],
            [0, 0, null, null], [10, 10, null, null]];
    }

    #[DataProvider('answerChanges')]
    public function test_answer_clock_changes_only_for_a_new_selection(int $previous, int $answer, ?string $old, ?string $expected): void
    {
        $now = CarbonImmutable::parse('2026-10-17 00:01:00', 'Europe/Brussels');
        $old = $old ? CarbonImmutable::parse($old, 'Europe/Brussels') : null;
        $clock = StudyAnswerClock::next($previous, $answer, $old, $now);
        $this->assertSame($expected, $clock?->format('Y-m-d H:i:s'));
    }

    public function test_changed_answer_moves_to_next_week_but_identical_retry_keeps_friday(): void
    {
        $friday = CarbonImmutable::parse('2026-10-16 23:59:59', 'Europe/Brussels');
        $saturday = $friday->addSecond();
        $retry = StudyAnswerClock::next(1, 1, $friday, $saturday);
        $changed = StudyAnswerClock::next(1, 2, $friday, $saturday);
        $this->assertSame('2026-10-10', StudyCalendar::bounds($retry, 'Europe/Brussels')['start']->toDateString());
        $this->assertSame('2026-10-17', StudyCalendar::bounds($changed, 'Europe/Brussels')['start']->toDateString());
        $this->assertSame('2026-10-16 23:59:59', $friday->format('Y-m-d H:i:s'));
    }

    public function test_clearing_and_answering_again_gets_a_new_work_time(): void
    {
        $old = CarbonImmutable::parse('2026-10-14 12:00:00', 'Europe/Brussels');
        $later = $old->addDay();
        $cleared = StudyAnswerClock::next(10, 0, $old, $later);
        $this->assertNull($cleared);
        $this->assertEquals($later, StudyAnswerClock::next(0, 10, $cleared, $later));
    }

    public function test_manual_and_automatic_totals_merge_without_making_fake_pages_hours_or_fragments(): void
    {
        $days = StudyDailyTotals::merge([
            ['activity_date' => '2026-10-14', 'manual_questions' => 90, 'pages' => 25, 'minutes' => 150, 'fragments' => 3],
        ], [
            ['activity_date' => '2026-10-14', 'automatic_tests' => 10, 'automatic_exams' => 15],
            ['activity_date' => '2026-10-13', 'automatic_tests' => 5, 'automatic_exams' => 0],
        ]);
        $this->assertSame(['2026-10-13', '2026-10-14'], array_column($days, 'activity_date'));
        $this->assertSame(115, $days[1]['questions']);
        $this->assertSame(25, $days[1]['pages']);
        $this->assertSame(150, $days[1]['minutes']);
        $this->assertSame(3, $days[1]['fragments']);
        $this->assertSame(5, $days[0]['questions']);
        $this->assertSame(0, $days[0]['minutes']);
        $this->assertSame(0, $days[0]['fragments']);
    }

    public function test_zero_manual_record_is_distinct_from_no_work_and_empty_sources_stay_empty(): void
    {
        $this->assertSame([], StudyDailyTotals::merge([], []));
        $zero = StudyDailyTotals::merge([['activity_date' => '2026-10-14', 'fragments' => 1]], []);
        $this->assertCount(1, $zero);
        $this->assertSame(0, $zero[0]['questions']);
        $this->assertSame(1, $zero[0]['fragments']);
    }
}
