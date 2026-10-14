<?php

namespace App\Jobs;

use App\Actions\Communications\AuthorizeCommunication;
use App\Actions\Study\AuthorizeStudyPanel;
use App\Helpers\OneSignalClient;
use App\Helpers\WebPushSettings;
use App\Mail\CommunicationMail;
use App\Models\CommunicationDelivery;
use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;

class DeliverCommunication implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public int $deliveryId) {}

    public function failed(?Throwable $exception): void
    {
        // A stopped worker leaves delivery uncertain; never automatically resend it.
        // Keep terminal records unchanged and never retain the raw exception text.
        CommunicationDelivery::whereKey($this->deliveryId)->whereIn('status', ['scheduled', 'sending'])
            ->update(['status' => 'failed', 'error_code' => 'worker_interrupted', 'completed_at' => now()]);
    }

    public function handle(): void
    {
        $candidate = CommunicationDelivery::find($this->deliveryId);
        if (! $candidate || $candidate->status !== 'scheduled') {
            return;
        }
        // Serialize the counter check across workers; RateLimiter::attempt alone is not atomic.
        $rateLock = Cache::lock('communications-rate:'.$candidate->channel, 10);
        $allowed = false;
        if ($rateLock->get()) {
            try {
                $allowed = RateLimiter::attempt('communications-send:'.$candidate->channel, 5, fn () => true, 60);
            } finally {
                $rateLock->release();
            }
        }
        if (! $allowed) {
            CommunicationDelivery::whereKey($candidate->id)->where('status', 'scheduled')->update(['status' => 'pending', 'available_at' => now()->addMinute(), 'claimed_at' => null]);

            return;
        }
        $previousActor = Auth::user();
        $delivery = DB::transaction(function () {
            $row = CommunicationDelivery::whereKey($this->deliveryId)->lockForUpdate()->first();
            if (! $row || $row->status !== 'scheduled') {
                return null;
            }
            $row->update(['status' => 'sending']);

            return $row;
        });
        if (! $delivery) {
            return;
        }
        try {
            $recipient = User::find($delivery->user_id);
            $actor = User::find($delivery->actor_id);
            if (! $recipient || ! $actor || $recipient->status->value !== 'active' || $actor->status->value !== 'active') {
                throw new RuntimeException('inactive_account');
            }
            Auth::setUser($actor);
            if ($delivery->source_key) {
                AuthorizeStudyPanel::run($recipient, true);
            } else {
                AuthorizeCommunication::run($recipient, true);
            }
            if ($delivery->channel === 'push') {
                $local = WebPushSubscription::where('user_id', $recipient->id)->where('app_id', WebPushSettings::appId())->whereNull('revoked_at')->pluck('subscription_id')->all();
                if (! $local) {
                    throw new RuntimeException('no_subscription');
                }
                $verified = array_column(OneSignalClient::subscriptions($recipient), 'id');
                $ids = array_values(array_intersect($local, $verified));
                $provider = OneSignalClient::send($ids, $delivery->title, $delivery->body, $delivery->path, $delivery->idempotency_key);
                $delivery->provider_id = $provider;
            } else {
                if (! settings()->mailHost || ! settings()->mailFromEmail) {
                    throw new RuntimeException('mail_not_configured');
                }
                $email = $recipient->email ?: $recipient->username;
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('invalid_email');
                }
                Mail::to($email)->send(new CommunicationMail($delivery->title, $delivery->body));
            }
            $delivery->fill(['status' => 'sent', 'completed_at' => now(), 'error_code' => null])->save();
        } catch (Throwable $error) {
            // Never retain provider payloads, credentials or SMTP messages in UI/error logs.
            $code = $error instanceof RuntimeException && preg_match('/^[a-z0-9_]+$/', $error->getMessage()) ? $error->getMessage() : 'delivery_failed';
            $delivery->update(['status' => in_array($code, ['no_subscription', 'inactive_account'], true) ? 'skipped' : 'failed', 'error_code' => $code, 'completed_at' => now()]);
        } finally {
            if ($previousActor) {
                Auth::setUser($previousActor);
            } else {
                Auth::forgetUser();
            }
        }
    }
}
