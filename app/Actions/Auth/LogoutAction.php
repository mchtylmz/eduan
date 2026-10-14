<?php

namespace App\Actions\Auth;

use App\Actions\Notifications\RevokeWebSubscription;
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
            table: (new User)->getTable(),
            logType: 'logout',
            data: user()->toArray()
        );

        if ($id = request()->session()->get('webPushSubscriptionRecordId')) {
            try {
                RevokeWebSubscription::run((int) $id);
            } catch (\Throwable) {
                // Logout must remain available; local targeting is disabled before provider requests.
            }
        }
        Auth::logout();

        request()->session()->regenerate(true);
    }
}
