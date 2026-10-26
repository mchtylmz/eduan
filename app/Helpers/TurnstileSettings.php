<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;
use Throwable;

class TurnstileSettings
{
    public const KEYS = ['turnstileEnabled', 'turnstileSiteKey', 'turnstileSecretKey'];

    public static function enabled(): bool
    {
        return (string) settings()->turnstileEnabled === '1';
    }

    public static function siteKey(): string
    {
        return (string) settings()->turnstileSiteKey;
    }

    public static function secret(): string
    {
        try {
            return settings()->turnstileSecretKey ? Crypt::decryptString(settings()->turnstileSecretKey) : '';
        } catch (Throwable) {
            return '';
        }
    }
}
