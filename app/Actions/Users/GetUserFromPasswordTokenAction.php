<?php

namespace App\Actions\Users;

use App\Enums\StatusEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class GetUserFromPasswordTokenAction
{
    use AsAction;

    public function handle(string $token): false|User
    {
        if (! preg_match('/^[A-Za-z0-9]{40}$/D', $token)) {
            return false;
        }
        $passwordToken = DB::table('password_reset_tokens')
            ->whereIn('token', [$token, hash('sha256', $token)])
            ->where('created_at', '>', now()->subMinutes(config('auth.passwords.users.expire', 60)))
            ->where('created_at', '<=', now())->first();
        if (! $passwordToken || (! hash_equals($passwordToken->token, $token)
                && ! hash_equals($passwordToken->token, hash('sha256', $token)))) {
            return false;
        }

        $user = User::where('email', $passwordToken->email)->where('status', StatusEnum::ACTIVE)->first();
        if (!$user) {
            return false;
        }

        return $user;
    }
}
