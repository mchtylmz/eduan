<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class StudyAnswerClock
{
    /** Only a real answer change moves the work date; grading and retries do not. */
    public static function next(int $previousAnswer, int $answer, ?DateTimeInterface $previousTime, DateTimeInterface $changedAt): ?CarbonImmutable
    {
        if ($answer === 0) {
            return null;
        }
        if ($previousAnswer === $answer) {
            return $previousTime ? CarbonImmutable::instance($previousTime) : null;
        }

        return CarbonImmutable::instance($changedAt);
    }
}
