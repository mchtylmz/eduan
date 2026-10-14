<?php

namespace Tests\Unit;

use App\Helpers\StudyInput;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudyInputTest extends TestCase
{
    public static function counts(): array
    {
        return [[null, 0], ['', 0], ['  ', 0], [0, 0], ['00001', 1], [' 150 ', 150], ['4294967295', 4294967295], ['0004294967295', 4294967295]];
    }

    #[DataProvider('counts')]
    public function test_valid_counts(mixed $input, int $expected): void
    {
        $this->assertSame($expected, StudyInput::count($input));
    }

    public static function invalidCounts(): array
    {
        return [[-1], ['-1'], ['1.5'], ['1e2'], ['+2'], [true], [false], [[]], [1.0], ['4294967296'], ['99999999999999999999'], ['1 000'], ['１']];
    }

    #[DataProvider('invalidCounts')]
    public function test_invalid_counts_are_rejected(mixed $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        StudyInput::count($input);
    }

    public static function durations(): array
    {
        return [['', 0], [0, 0], ['1,5', 90], ['2.5', 150], ['0.0083', 0], ['0.0084', 1], ['0.025', 2], ['1.2345', 74], ['71582788.25', 4294967295]];
    }

    #[DataProvider('durations')]
    public function test_hours_round_to_whole_minutes_without_float_arithmetic(mixed $hours, int $minutes): void
    {
        $this->assertSame($minutes, StudyInput::minutes($hours));
    }

    public static function invalidHours(): array
    {
        return [['-1'], ['0.12345'], ['.5'], ['1.'], ['1e3'], [1.5], [[]], [true], ['71582789'], ['71582788.26'], ['999999999999']];
    }

    #[DataProvider('invalidHours')]
    public function test_invalid_or_overflowing_hours_are_rejected(mixed $hours): void
    {
        $this->expectException(InvalidArgumentException::class);
        StudyInput::minutes($hours);
    }

    public function test_blank_targets_stay_nullable_and_explicit_zero_is_distinct(): void
    {
        $this->assertSame(['questions' => null, 'pages' => null, 'minutes' => null], StudyInput::values([], target: true));
        $this->assertSame(['questions' => 0, 'pages' => 0, 'minutes' => 0], StudyInput::values(['questions' => '0', 'pages' => 0, 'hours' => '0'], target: true));
        $this->assertSame(['questions' => 0, 'pages' => 0, 'minutes' => 0], StudyInput::values([]));
        $this->assertNull(StudyInput::minutes('  ', nullable: true));
        $this->assertSame('', StudyInput::hours(null));
    }

    public function test_minute_values_survive_form_display_and_resubmission(): void
    {
        foreach ([...range(0, 120), 145, 9999, 4294967295] as $minutes) {
            $this->assertSame($minutes, StudyInput::minutes(StudyInput::hours($minutes)), (string) $minutes);
        }
    }

    public function test_normalization_names_the_invalid_field_and_ignores_extra_input(): void
    {
        $this->assertSame(['questions' => 10, 'pages' => 20, 'minutes' => 90], StudyInput::values(['questions' => '10', 'pages' => 20, 'hours' => '1,5', 'user_id' => 99]));
        foreach (['questions', 'pages', 'hours'] as $field) {
            try {
                StudyInput::values([$field => '-1']);
                $this->fail('Invalid field accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame($field, $exception->getMessage());
            }
        }
    }
}
