<?php

namespace Tests\Unit;

use App\Helpers\LeagueRules;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LeagueRulesTest extends TestCase
{
    public static function sources(): array
    {
        return [
            ['App\\Models\\ExamResultDetail', 'exam_result_details', 'exam_results', 'exam_result_id'],
            ['App\\Models\\TestsResultDetail', 'tests_result_details', 'tests_results', 'tests_result_id'],
        ];
    }

    #[DataProvider('sources')]
    public function test_source_mapping_and_frozen_settings_roundtrip(string $model, string $source, string $base, string $foreign): void
    {
        $rules = new LeagueRules(LeagueRules::sourceForModel($model), 3, -1, 10);
        $restored = LeagueRules::fromSnapshot($source, $rules->snapshot());
        $this->assertSame($source, $restored->source);
        $this->assertSame($base, $restored->baseTable());
        $this->assertSame($foreign, $restored->foreignId());
        $this->assertSame(['correct_point' => 3, 'incorrect_point' => -1, 'user_count' => 10], $restored->snapshot());
    }

    public static function invalidRules(): array
    {
        return [['users', 3, -1, 10], ['exam_result_details', 0, -1, 10], ['exam_result_details', 100, -1, 10],
            ['exam_result_details', 3, -100, 10], ['exam_result_details', 3, 100, 10],
            ['exam_result_details', 3, -1, 0], ['exam_result_details', 3, -1, 51]];
    }

    #[DataProvider('invalidRules')]
    public function test_invalid_source_scoring_and_capacity_are_rejected(string $source, int $correct, int $incorrect, int $count): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LeagueRules($source, $correct, $incorrect, $count);
    }

    public static function brokenSnapshots(): array
    {
        return [[[]], [['correct_point' => 3, 'incorrect_point' => -1]],
            [['correct_point' => '3', 'incorrect_point' => -1, 'user_count' => 10]],
            [['correct_point' => 3, 'incorrect_point' => -1, 'user_count' => null]]];
    }

    #[DataProvider('brokenSnapshots')]
    public function test_broken_snapshot_does_not_fall_back_to_current_settings(array $snapshot): void
    {
        $this->expectException(InvalidArgumentException::class);
        LeagueRules::fromSnapshot('exam_result_details', $snapshot);
    }

    public function test_unlisted_model_cannot_be_resolved_as_a_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        LeagueRules::sourceForModel('App\\Models\\User');
    }

    public function test_zero_and_positive_incorrect_points_keep_the_signed_setting(): void
    {
        foreach ([0, 2, -99, 99] as $points) {
            $rules = LeagueRules::fromSnapshot('tests_result_details', ['correct_point' => 99, 'incorrect_point' => $points, 'user_count' => 50]);
            $this->assertSame($points, $rules->incorrectPoint);
        }
    }
}
