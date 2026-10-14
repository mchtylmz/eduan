<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class WebPushSettings
{
    public const KEYS = ['notificationsEnabled', 'oneSignalAppId', 'oneSignalOrigin', 'oneSignalSafariId', 'oneSignalApiKey', 'oneSignalLocalAppId', 'oneSignalLocalApiKey'];

    public static function enabled(): bool
    {
        return (string) settings()->notificationsEnabled === '1';
    }

    public static function local(): bool
    {
        return ! app()->environment('production');
    }

    public static function appId(): string
    {
        return (string) (self::local() ? settings()->oneSignalLocalAppId : settings()->oneSignalAppId);
    }

    public static function key(): string
    {
        $encrypted = self::local() ? settings()->oneSignalLocalApiKey : settings()->oneSignalApiKey;
        if (! $encrypted) {
            return (string) config('services.onesignal.'.(self::local() ? 'local_api_key' : 'api_key'), '');
        }
        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return '';
        }
    }

    public static function ready(): bool
    {
        return self::enabled() && Str::isUuid(self::appId()) && self::key() !== '';
    }

    public static function externalId(User $user): string
    {
        return 'web-'.hash_hmac('sha256', (string) $user->id, (string) config('app.key'));
    }

    public static function origin(): string
    {
        return self::local() ? rtrim(config('app.url'), '/') : rtrim((string) settings()->oneSignalOrigin, '/');
    }

    public static function publicConfig(): array
    {
        return ['appId' => self::appId(), 'safari_web_id' => self::local() ? '' : (string) settings()->oneSignalSafariId,
            'allowLocalhostAsSecureOrigin' => self::local(), 'notifyButton' => ['enable' => false],
            'welcomeNotification' => ['disable' => true], 'promptOptions' => ['slidedown' => ['prompts' => []]],
            'serviceWorkerPath' => 'push/onesignal/OneSignalSDKWorker.js', 'serviceWorkerParam' => ['scope' => '/push/onesignal/'],
            'autoResubscribe' => false, 'requiresUserPrivacyConsent' => true];
    }
}
