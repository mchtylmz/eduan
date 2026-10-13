<?php

namespace App\Helpers;

class LogDataMasker
{
    public static function mask(array $data): array
    {
        $namedSecret = isset($data['name']) && is_string($data['name']) && self::sensitive($data['name']);
        foreach ($data as $key => $value) {
            if (self::sensitive((string) $key) || ($namedSecret && $key === 'value')) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = self::mask($value);
            } elseif (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $masked = self::mask($decoded);
                    if ($masked !== $decoded) {
                        $data[$key] = json_encode($masked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }
        }

        return $data;
    }

    private static function sensitive(string $key): bool
    {
        $key = preg_replace('/[^a-z0-9]/', '', strtolower($key));

        foreach (['password', 'passwd', 'pwd', 'token', 'secret', 'apikey', 'privatekey', 'accesskey', 'authorization'] as $word) {
            if (str_contains($key, $word)) {
                return true;
            }
        }

        return false;
    }
}
