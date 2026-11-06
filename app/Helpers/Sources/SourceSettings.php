<?php

namespace App\Helpers\Sources;

use Illuminate\Support\Facades\Crypt;
use Throwable;

class SourceSettings
{
    public const TYPES = ['pdf' => 'application/pdf', 'html' => 'text/html', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public const KEYS = ['sourceStorage', 'sourceMaxMb', 'sourceTypes', 'sourceR2Account', 'sourceR2Bucket', 'sourceR2Key', 'sourceR2Secret', 'sourceR2PublicUrl'];

    public static function types(): array
    {
        $value = settings()->sourceTypes;

        return $value === null ? array_keys(self::TYPES) : array_values(array_intersect(array_keys(self::TYPES), explode(',', $value)));
    }

    public static function maxBytes(): int
    {
        return max(1, min(100, (int) (settings()->sourceMaxMb ?: 100))) * 1024 * 1024;
    }

    public static function cloud(): bool
    {
        return settings()->sourceStorage === 'r2';
    }

    public static function secret(): string
    {
        try {
            return settings()->sourceR2Secret ? Crypt::decryptString(settings()->sourceR2Secret) : '';
        } catch (Throwable) {
            return '';
        }
    }

    public static function cloudConfig(): array
    {
        return ['driver' => 's3', 'key' => (string) settings()->sourceR2Key, 'secret' => self::secret(),
            'region' => 'auto', 'bucket' => (string) settings()->sourceR2Bucket,
            'endpoint' => 'https://'.settings()->sourceR2Account.'.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => true, 'http' => ['connect_timeout' => 10, 'timeout' => 120], 'retries' => 1, 'request_checksum_calculation' => 'when_required', 'response_checksum_validation' => 'when_required', 'throw' => true];
    }
}
