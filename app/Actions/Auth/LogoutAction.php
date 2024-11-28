<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class LogoutAction
{
    use AsAction, Loggable;

    public function handle(): void
    {
        $this->customLog(
            table: (new User())->getTable(),
            logType: 'logout',
            data: user()->toArray()
        );

        Auth::logout();

        request()->session()->regenerate(true);
    }
}
