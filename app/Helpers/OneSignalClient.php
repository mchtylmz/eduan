<?php

namespace App\Helpers;

use App\Exceptions\OneSignalFailure;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OneSignalClient
{
    private static function request(): PendingRequest
    {
        if (! WebPushSettings::ready()) {
            throw new OneSignalFailure('not_configured');
        }

        return Http::baseUrl('https://api.onesignal.com')->withToken(WebPushSettings::key(), 'Key')->acceptJson()->asJson()->connectTimeout(5)->timeout(20);
    }

    private static function call(string $method, string $path, array $data = []): Response
    {
        try {
            return self::request()->$method($path, $data);
        } catch (ConnectionException) {
            throw new OneSignalFailure('provider_unavailable');
        }
    }

    public static function subscriptions(User $user): array
    {
        $response = self::call('get', '/apps/'.WebPushSettings::appId().'/users/by/external_id/'.WebPushSettings::externalId($user));
        if ($response->status() === 404) {
            return [];
        }
        if (! $response->successful()) {
            throw new OneSignalFailure('provider_http_'.$response->status());
        }
        $data = $response->json();
        if (! is_array($data) || ($data['identity']['external_id'] ?? '') !== WebPushSettings::externalId($user) || ! is_array($data['subscriptions'] ?? null)) {
            throw new OneSignalFailure('provider_invalid_identity');
        }

        return array_values(array_filter($data['subscriptions'], fn ($s) => is_array($s) && Str::isUuid($s['id'] ?? '')
            && ($s['app_id'] ?? '') === WebPushSettings::appId() && in_array($s['type'] ?? '', ['ChromePush', 'FirefoxPush', 'SafariPush'], true) && ($s['enabled'] ?? false) === true));
    }

    public static function revoke(string $subscription): void
    {
        $response = self::call('patch', '/apps/'.WebPushSettings::appId().'/subscriptions/'.$subscription, ['subscription' => ['enabled' => false]]);
        if (! $response->successful() && $response->status() !== 404) {
            throw new OneSignalFailure('provider_http_'.$response->status());
        }
    }

    public static function send(array $subscriptions, string $title, string $body, string $path, string $key): string
    {
        if (! $subscriptions) {
            throw new OneSignalFailure('no_subscription');
        }
        $response = self::call('post', '/notifications', ['app_id' => WebPushSettings::appId(), 'target_channel' => 'push',
            'include_subscription_ids' => $subscriptions, 'headings' => ['en' => $title], 'contents' => ['en' => $body],
            'url' => WebPushSettings::origin().$path, 'idempotency_key' => $key]);
        if (! $response->successful()) {
            throw new OneSignalFailure('provider_http_'.$response->status());
        }
        $id = $response->json('id');
        if (! is_string($id) || ! Str::isUuid($id)) {
            throw new OneSignalFailure('provider_invalid_response');
        }

        return $id;
    }
}
