<?php

namespace App\Actions\Auth;

use App\Enums\StatusEnum;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class RecoverPasswordAction
{
    use AsAction, Loggable;

    public function handle(string $username): bool|User
    {
        $user = User::where('status', StatusEnum::ACTIVE)->where('username', $username)->first();
        if (!$user) {
            return false;
        }

        $this->customLog(
            table: $user->getTable(),
            logType: 'recover-password',
            data: $user->toArray()
        );

        return $user;
    }
}
