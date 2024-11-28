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
        $passwordToken = DB::table('password_reset_tokens')->where('token', $token)->first();
        if (!$passwordToken) {
            return false;
        }

        $user = User::where('email', $passwordToken->email)->where('status', StatusEnum::ACTIVE)->first();
        if (!$user) {
            return false;
        }
        flush();

        return $user;
    }
}
