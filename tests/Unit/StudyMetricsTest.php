<?php

namespace Tests\Unit;

use App\Helpers\StudyMetrics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudyMetricsTest extends TestCase
{
    public function test_actual_percent_and_overall_average_can_exceed_one_hundred(): void
    {
        $rates = StudyMetrics::achievement(['questions' => 100, 'pages' => 50, 'minutes' => 60], ['questions' => 150, 'pages' => 50, 'minutes' => 120]);
        $this->assertSame(150.0, $rates['questions']);
        $this->assertSame(150.0, $rates['overall']);
    }

    public function test_zero_and_empty_goals_are_excluded_not_treated_as_zero_achievement(): void
    {
        $this->assertSame(50.0, StudyMetrics::achievement(['questions' => 100, 'pages' => 0, 'minutes' => null], ['questions' => 50, 'pages' => 99, 'minutes' => 999])['overall']);
        $this->assertNull(StudyMetrics::achievement([], ['questions' => 999])['overall']);
    }

    public static function topicCases(): array
    {
        return [[8, 2, 10, 'strong'], [7, 3, 10, 'balanced'], [3, 7, 10, 'balanced'], [2, 8, 10, 'weak'], [9, 0, 10, 'insufficient'], [0, 0, 10, 'insufficient'], [1, 0, 1, 'strong']];
    }

    #[DataProvider('topicCases')]
    public function test_topic_threshold_and_minimum_are_exact(int $correct, int $incorrect, int $minimum, string $label): void
    {
        $topic = StudyMetrics::topic($correct, $incorrect, $minimum);
        $this->assertSame($label, $topic['label']);
        $this->assertSame($correct + $incorrect, $topic['answered']);
    }

    public function test_percentiles_are_strict_and_ties_do_not_invent_a_top_group(): void
    {
        $top = StudyMetrics::percentile(10, range(1, 10));
        $this->assertSame(10.0, $top['top']);
        $this->assertSame(90.0, $top['lower']);
        $ties = StudyMetrics::percentile(10, [10, 10, 10]);
        $this->assertSame(100.0, $ties['top']);
        $this->assertSame(0.0, $ties['lower']);
        $this->assertSame(1, $ties['rank']);
        $this->assertSame(100.0, StudyMetrics::percentile(0, [0])['top']);
    }

    public function test_missing_accuracy_is_excluded_and_no_population_returns_no_rank(): void
    {
        $this->assertNull(StudyMetrics::percentile(1, []));
        $this->assertSame(2, StudyMetrics::percentile(50,[50, 100, null])['size']);
    }
}
