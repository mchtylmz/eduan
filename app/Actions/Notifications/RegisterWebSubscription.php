<?php

namespace App\Actions\Notifications;

use App\Helpers\OneSignalClient;
use App\Helpers\WebPushSettings;
use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class RegisterWebSubscription
{
    use AsAction;

    public function handle(User $user, array $input): WebPushSubscription
    {
        abort_unless(auth()->id() === $user->id, 403);
        $values = Validator::make($input, ['subscription_id' => 'required|uuid', 'label' => 'required|string|max:120', 'consent' => 'accepted', 'explicit_consent' => 'required|boolean'])->validate();
        abort_unless(WebPushSettings::ready(), 503, __('notifications.not_configured'));
        $owned = collect(OneSignalClient::subscriptions($user))->contains('id', $values['subscription_id']);
        abort_unless($owned, 422, __('notifications.subscription_unverified'));

        return DB::transaction(function () use ($user, $values) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = WebPushSubscription::where('app_id', WebPushSettings::appId())->where('subscription_id', $values['subscription_id'])->lockForUpdate()->first();
            abort_unless(($existing && ! $existing->revoked_at && $existing->user_id === $user->id) || $values['explicit_consent'], 409, __('notifications.consent_required'));
            // Shared browsers can move only after provider identity was verified for the signed-in account.
            $existing ??= new WebPushSubscription(['app_id' => WebPushSettings::appId(), 'subscription_id' => $values['subscription_id']]);
            $existing->fill(['user_id' => $user->id, 'label' => $values['label'], 'consented_at' => $existing->user_id === $user->id && ! $existing->revoked_at ? ($existing->consented_at ?? now()) : now(),
                'verified_at' => now(), 'revoked_at' => null, 'provider_revoked_at' => null])->save();

            return $existing;
        });
    }
}
