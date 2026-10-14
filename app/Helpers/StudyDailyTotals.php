<?php

namespace App\Helpers;

class StudyDailyTotals
{
    /** Combine already aggregated sources; automatic questions never create manual fragments. */
    public static function merge(array $manual, array $automatic): array
    {
        $days = [];
        foreach ([$manual, $automatic] as $rows) {
            foreach ($rows as $row) {
                $date = $row['activity_date'];
                $days[$date] ??= ['activity_date' => $date, 'manual_questions' => 0, 'automatic_tests' => 0,
                    'automatic_exams' => 0, 'questions' => 0, 'pages' => 0, 'minutes' => 0, 'fragments' => 0];
                foreach (['manual_questions', 'automatic_tests', 'automatic_exams', 'pages', 'minutes', 'fragments'] as $field) {
                    $days[$date][$field] += (int) ($row[$field] ?? 0);
                }
            }
        }
        ksort($days);
        foreach ($days as &$day) {
            $day['questions'] = $day['manual_questions'] + $day['automatic_tests'] + $day['automatic_exams'];
        }

        return array_values($days);
    }
}
