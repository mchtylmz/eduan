<?php

namespace App\Actions\Notifications;

use App\Helpers\OneSignalClient;
use App\Helpers\WebPushSettings;
use App\Models\WebPushSubscription;
use Lorisleiva\Actions\Concerns\AsAction;

class RevokeWebSubscription
{
    use AsAction;

    public function handle(int $id): void
    {
        $record = WebPushSubscription::where('user_id', auth()->id())->whereKey($id)->firstOrFail();
        // Local targeting stops immediately even if the provider is temporarily unavailable.
        $record->update(['revoked_at' => $record->revoked_at ?? now()]);
        if ($record->app_id === WebPushSettings::appId() && WebPushSettings::ready()) {
            $owned = collect(OneSignalClient::subscriptions(auth()->user()))->contains('id', $record->subscription_id);
            if ($owned) {
                OneSignalClient::revoke($record->subscription_id);
            }
            $record->update(['provider_revoked_at' => now()]);
        }
    }
}
