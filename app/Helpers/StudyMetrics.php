<?php

namespace App\Helpers;

class StudyMetrics
{
    public static function achievement(array $target, array $actual): array
    {
        $rates = [];
        foreach (['questions', 'pages', 'minutes'] as $field) {
            $goal = (int) ($target[$field] ?? 0);
            $rates[$field] = $goal > 0 ? 100 * (int) ($actual[$field] ?? 0) / $goal : null;
        }
        $included = array_filter($rates, fn ($value) => $value !== null);
        $rates['overall'] = $included ? array_sum($included) / count($included) : null;

        return array_map(fn ($value) => $value === null ? null : round($value, 1), $rates);
    }

    public static function topic(int $correct, int $incorrect, int $minimum): array
    {
        $total = $correct + $incorrect;
        $rate = $total ? round(100 * $correct / $total, 1) : null;
        $label = $total < $minimum ? 'insufficient' : ($correct * 100 > $total * 70 ? 'strong' : ($incorrect * 100 > $total * 70 ? 'weak' : 'balanced'));

        return ['correct' => $correct, 'incorrect' => $incorrect, 'answered' => $total, 'rate' => $rate, 'label' => $label];
    }

    public static function percentile(float $value, array $population): ?array
    {
        $population = array_values(array_filter($population, fn ($score) => $score !== null));
        if (! $population) {
            return null;
        }
        $higher = count(array_filter($population, fn ($score) => $score > $value));
        $equal = count(array_filter($population, fn ($score) => $score == $value));
        $lower = count($population) - $higher - $equal;

        // Ties share a rank; show the end of the tied band, never an invented top place.
        return ['size' => count($population), 'rank' => $higher + 1,
            'top' => round(100 * ($higher + $equal) / count($population), 1),
            'lower' => round(100 * $lower / count($population), 1)];
    }
}
