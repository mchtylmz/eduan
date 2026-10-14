<?php

namespace App\Helpers;

class StudyTrackingSettings
{
    public const KEYS = ['studyTrackingEnabled', 'studyTrackingStartedOn', 'studyAllowPastActivities', 'studyMinimumTopicQuestions'];

    public static function enabled(): bool
    {
        return (string) settings()->studyTrackingEnabled === '1';
    }

    public static function startedOn(): ?string
    {
        $value = settings()->studyTrackingStartedOn;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function allowPastActivities(): bool
    {
        return (string) settings()->studyAllowPastActivities === '1';
    }

    public static function minimumTopicQuestions(): int
    {
        $value = filter_var(settings()->studyMinimumTopicQuestions, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);

        return $value === false ? 10 : $value;
    }

    public static function contains(array $values): bool
    {
        foreach (array_keys($values) as $key) {
            if (is_string($key) && str_starts_with($key, 'study')) {
                return true;
            }
        }

        return false;
    }
}
