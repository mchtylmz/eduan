<?php

namespace App\Actions\Auth;

use App\Helpers\TurnstileSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class VerifyTurnstile
{
    use AsAction;

    public function handle(string $token, string $action): void
    {
        if (! TurnstileSettings::enabled() || auth()->check()) {
            return;
        }

        $valid = false;
        if ($token !== '' && strlen($token) <= 2048 && TurnstileSettings::siteKey() !== '' && TurnstileSettings::secret() !== '') {
            try {
                $response = Http::asForm()->acceptJson()->connectTimeout(3)->timeout(8)
                    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => TurnstileSettings::secret(),
                        'response' => $token,
                    ]);
                $data = $response->json();
                $hostname = parse_url(config('app.url'), PHP_URL_HOST);
                $valid = $response->successful() && is_array($data) && ($data['success'] ?? null) === true
                    && is_string($hostname) && $hostname !== ''
                    && ($data['hostname'] ?? null) === $hostname && ($data['action'] ?? null) === $action;
            } catch (ConnectionException) {
                // Never log the request/exception: it contains the provider secret and token.
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages(['turnstileToken' => __('turnstile.failed')]);
        }
    }
}
