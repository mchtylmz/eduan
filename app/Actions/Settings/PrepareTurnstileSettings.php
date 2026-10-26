<?php

namespace App\Actions\Settings;

use App\Helpers\TurnstileSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class PrepareTurnstileSettings
{
    use AsAction;

    public function handle(array $values): array
    {
        $keys = array_filter(array_keys($values), fn ($key) => str_starts_with($key, 'turnstile'));
        if (! $keys) {
            return $values;
        }
        abort_unless(auth()->user()?->can('user-type:admin'), 403);
        foreach ($keys as $key) {
            if (! in_array($key, TurnstileSettings::KEYS, true)) {
                throw ValidationException::withMessages(['settings.'.$key => __('turnstile.invalid_settings')]);
            }
        }
        Validator::make(['settings' => $values], [
            'settings.turnstileEnabled' => 'sometimes|required|in:0,1',
            'settings.turnstileSiteKey' => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9_-]+$/D'],
            'settings.turnstileSecretKey' => ['nullable', 'string', 'max:200', 'regex:/^[a-zA-Z0-9_-]+$/D'],
        ])->validate();

        $siteKey = array_key_exists('turnstileSiteKey', $values) ? trim((string) $values['turnstileSiteKey']) : TurnstileSettings::siteKey();
        if (array_key_exists('turnstileSiteKey', $values)) {
            $values['turnstileSiteKey'] = $siteKey;
        }
        $secret = trim($values['turnstileSecretKey'] ?? '');
        $enabled = (string) ($values['turnstileEnabled'] ?? (TurnstileSettings::enabled() ? '1' : '0')) === '1';
        if ($enabled && ($siteKey === '' || ($secret === '' && TurnstileSettings::secret() === ''))) {
            throw ValidationException::withMessages(['settings.turnstileEnabled' => __('turnstile.keys_required')]);
        }
        if (array_key_exists('turnstileSecretKey', $values)) {
            if ($secret === '') {
                unset($values['turnstileSecretKey']);
            } else {
                $values['turnstileSecretKey'] = Crypt::encryptString($secret);
            }
        }

        return $values;
    }
}
