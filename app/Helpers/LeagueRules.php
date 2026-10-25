<?php

namespace App\Helpers;

use InvalidArgumentException;

class LeagueRules
{
    public function __construct(
        public readonly string $source,
        public readonly int $correctPoint,
        public readonly int $incorrectPoint,
        public readonly int $userCount,
    ) {
        if (! in_array($source, ['exam_result_details', 'tests_result_details'], true)
            || $correctPoint < 1 || $correctPoint > 99 || $incorrectPoint < -99 || $incorrectPoint > 99
            || $userCount < 1 || $userCount > 50) {
            throw new InvalidArgumentException('Invalid league calculation settings.');
        }
    }

    public static function sourceForModel(string $model): string
    {
        return match ($model) {
            'App\\Models\\ExamResultDetail' => 'exam_result_details',
            'App\\Models\\TestsResultDetail' => 'tests_result_details',
            default => throw new InvalidArgumentException('Invalid league result model.'),
        };
    }

    public static function fromSnapshot(string $source, array $snapshot): self
    {
        foreach (['correct_point', 'incorrect_point', 'user_count'] as $key) {
            if (! is_int($snapshot[$key] ?? null)) {
                throw new InvalidArgumentException('Incomplete league calculation settings.');
            }
        }

        return new self($source, $snapshot['correct_point'], $snapshot['incorrect_point'], $snapshot['user_count']);
    }

    public function snapshot(): array
    {
        return ['correct_point' => $this->correctPoint, 'incorrect_point' => $this->incorrectPoint, 'user_count' => $this->userCount];
    }

    public function baseTable(): string
    {
        return $this->source === 'exam_result_details' ? 'exam_results' : 'tests_results';
    }

    public function foreignId(): string
    {
        return $this->source === 'exam_result_details' ? 'exam_result_id' : 'tests_result_id';
    }
}
