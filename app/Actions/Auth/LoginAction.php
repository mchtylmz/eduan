<?php

namespace App\Actions\Auth;

use App\Enums\StatusEnum;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class LoginAction
{
    use AsAction, Loggable;

    public function handle(string $username, string $password, bool $remember = false): bool
    {
        $credentials = [
            'username' => $username,
            'password' => $password,
            'status' => StatusEnum::ACTIVE
        ];

        if (!Auth::attempt($credentials, $remember)) {
            return false;
        }

        $this->updateAtLastLogin();

        $this->customLog(
            table: (new User())->getTable(),
            logType: 'login',
            data: user()->toArray()
        );

        request()->session()->regenerate(true);

        return true;
    }

    protected function updateAtLastLogin(): void
    {
        request()->user()->update(['last_login_at' => now()]);
    }
}
