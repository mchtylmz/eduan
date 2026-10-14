<?php

namespace App\Actions\Notifications;

use App\Helpers\WebPushSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class PrepareNotificationSettings
{
    use AsAction;

    public function handle(array $values): array
    {
        if (! collect(array_keys($values))->contains(fn ($key) => str_starts_with($key, 'oneSignal') || str_starts_with($key, 'notifications'))) {
            return $values;
        }
        abort_unless(auth()->user()?->can('user-type:admin'), 403);
        foreach ($values as $key => $value) {
            if ((str_starts_with($key, 'oneSignal') || str_starts_with($key, 'notifications')) && ! in_array($key, WebPushSettings::KEYS, true)) {
                throw ValidationException::withMessages(['settings.'.$key => __('notifications.invalid_settings')]);
            }
        }
        Validator::make(['settings' => $values], [
            'settings.notificationsEnabled' => 'sometimes|required|in:0,1',
            'settings.oneSignalAppId' => 'nullable|uuid', 'settings.oneSignalLocalAppId' => 'nullable|uuid',
            'settings.oneSignalOrigin' => 'nullable|url:https|max:255', 'settings.oneSignalSafariId' => 'nullable|string|max:150',
            'settings.oneSignalApiKey' => 'nullable|string|max:2000', 'settings.oneSignalLocalApiKey' => 'nullable|string|max:2000',
        ])->validate();
        if (! empty($values['oneSignalOrigin'])) {
            $parts = parse_url($values['oneSignalOrigin']);
            if (! empty($parts['user']) || ! empty($parts['pass']) || ! empty($parts['query']) || ! empty($parts['fragment']) || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
                throw ValidationException::withMessages(['settings.oneSignalOrigin' => __('notifications.invalid_settings')]);
            }
            $values['oneSignalOrigin'] = rtrim($values['oneSignalOrigin'], '/');
        }
        foreach (['oneSignalApiKey', 'oneSignalLocalApiKey'] as $key) {
            if (array_key_exists($key, $values)) {
                if (trim($values[$key] ?? '') === '') {
                    unset($values[$key]);
                } else {
                    $values[$key] = Crypt::encryptString(trim($values[$key]));
                }
            }
        }

        return $values;
    }
}
