<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Str;

class PanelGuide
{
    public static function visibleTo(?User $user): bool
    {
        return $user?->hasRole('Yönetici', 'web') ?? false;
    }

    public static function url(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $url = trim($value);

        return $url !== '' && mb_strlen($url) <= 2048 && Str::isUrl($url, ['http', 'https']) ? $url : null;
    }
}
