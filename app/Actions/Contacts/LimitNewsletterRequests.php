<?php

namespace App\Actions\Contacts;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class LimitNewsletterRequests
{
    use AsAction;

    public function handle(): void
    {
        $key = 'newsletter:'.hash('sha256', (string) request()->ip());
        try {
            Cache::store(config('cache.limiter'))->lock($key.':lock', 5)->block(2, function () use ($key) {
                if (RateLimiter::tooManyAttempts($key, 1)) {
                    throw ValidationException::withMessages(['email' => __('turnstile.newsletter_limit')]);
                }
                RateLimiter::hit($key, 60);
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['email' => __('turnstile.newsletter_limit')]);
        }
    }
}
