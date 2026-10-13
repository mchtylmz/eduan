<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Lorisleiva\Actions\Concerns\AsAction;

class LimitAuthRequests
{
    use AsAction;

    public function handle(string $operation, ?string $username = null): string
    {
        $userId = $operation === 'login' ? User::where('username', $username)->value('id') : null;
        $identity = $userId ? 'user:'.$userId : 'name:'.mb_strtolower(trim($username ?? ''));
        $key = 'auth:'.$operation.':'.hash('sha256', request()->ip().'|'.$identity);
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429);
        RateLimiter::hit($key, 60);

        return $key;
    }
}
