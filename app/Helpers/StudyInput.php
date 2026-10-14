<?php

namespace App\Helpers;

use InvalidArgumentException;

class StudyInput
{
    private const MAX = 4294967295;

    public static function count(mixed $value, bool $nullable = false): ?int
    {
        if ($value === null || $value === '') {
            return $nullable ? null : 0;
        }
        if (! is_int($value) && ! is_string($value)) {
            throw new InvalidArgumentException;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return $nullable ? null : 0;
        }
        if (! preg_match('/^\d+$/D', $value)) {
            throw new InvalidArgumentException;
        }
        $digits = ltrim($value, '0') ?: '0';
        if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, (string) self::MAX) > 0)) {
            throw new InvalidArgumentException;
        }

        return (int) $digits;
    }

    public static function minutes(mixed $hours, bool $nullable = false): ?int
    {
        if ($hours === null || $hours === '') {
            return $nullable ? null : 0;
        }
        if (! is_int($hours) && ! is_string($hours)) {
            throw new InvalidArgumentException;
        }
        $hours = str_replace(',', '.', trim((string) $hours));
        if ($hours === '') {
            return $nullable ? null : 0;
        }
        if (! preg_match('/^(\d+)(?:\.(\d{1,4}))?$/D', $hours, $parts)) {
            throw new InvalidArgumentException;
        }
        $whole = self::count($parts[1]);
        if ($whole > intdiv(self::MAX, 60)) {
            throw new InvalidArgumentException;
        }
        $fraction = $parts[2] ?? '0';
        $scale = 10 ** strlen($fraction);
        $minutes = $whole * 60 + intdiv((int) $fraction * 60 + intdiv($scale, 2), $scale);
        if ($minutes > self::MAX) {
            throw new InvalidArgumentException;
        }

        return $minutes;
    }

    public static function hours(?int $minutes): string
    {
        if ($minutes === null) {
            return '';
        }
        $hundredths = intdiv($minutes * 100 + 30, 60);

        return rtrim(rtrim(sprintf('%d.%02d', intdiv($hundredths, 100), $hundredths % 100), '0'), '.');
    }

    public static function values(array $input, bool $target = false): array
    {
        $values = [];
        foreach (['questions', 'pages', 'hours'] as $field) {
            try {
                $values[$field === 'hours' ? 'minutes' : $field] = $field === 'hours'
                    ? self::minutes($input[$field] ?? null, $target)
                    : self::count($input[$field] ?? null, $target);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException($field);
            }
        }

        return $values;
    }
}
