<?php

namespace App\Actions\Communications;

use App\Helpers\WebPushSettings;
use App\Models\CommunicationBatch;
use App\Models\CommunicationDelivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateCommunicationBatch
{
    use AsAction;

    public function handle(array $input): CommunicationBatch
    {
        AuthorizeCommunication::run(write: true);
        $v = Validator::make($input, ['recipients' => 'required|array|min:1|max:500', 'recipients.*' => 'required|integer|distinct|exists:users,id',
            'channel' => 'required|in:email,push', 'title' => 'required|string|max:160', 'body' => 'required|string|max:10000',
            'locale' => 'required|in:tr,nl,en,fr', 'submission_key' => 'required|uuid'])->validate();
        abort_if($v['channel'] === 'push' && ! WebPushSettings::ready(), 422, __('notifications.not_configured'));
        abort_if($v['channel'] === 'email' && (! settings()->mailHost || ! settings()->mailFromEmail), 422, __('notifications.mail_not_configured'));
        $ids = array_map('intval', $v['recipients']);
        sort($ids);
        $v['body'] = trim($v['body']);
        $v['title'] = trim($v['title']);
        abort_if($v['title'] === '' || $v['body'] === '', 422);

        return DB::transaction(function () use ($v, $ids) {
            $actor = auth()->user();
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            AuthorizeCommunication::run(write: true);
            $recipients = User::whereIn('id', $ids)->get()->keyBy('id');
            abort_unless($recipients->count() === count($ids), 422);
            foreach ($recipients as $recipient) {
                AuthorizeCommunication::run($recipient, true);
                abort_unless($recipient->status->value === 'active', 422);
            }
            $values = ['channel' => $v['channel'], 'title' => $v['title'], 'body' => $v['body'], 'locale' => $v['locale'], 'recipient_ids' => $ids];
            $existing = CommunicationBatch::where('actor_id', $actor->id)->where('submission_key', $v['submission_key'])->first();
            if ($existing) {
                abort_unless($existing->only(array_keys($values)) === $values, 409, __('study.conflict'));

                return $existing;
            }
            $batch = CommunicationBatch::create($values + ['actor_id' => $actor->id, 'submission_key' => $v['submission_key']]);
            foreach ($ids as $index => $id) {
                $recipient = $recipients->get($id);
                // Plain-text supported variables are intentionally limited and escaped by the mail view.
                $replace = ['{{name}}' => $recipient->name, '{{surname}}' => $recipient->surname];
                CommunicationDelivery::create(['batch_id' => $batch->id, 'actor_id' => $actor->id, 'user_id' => $id, 'channel' => $v['channel'],
                    'title' => strtr($v['title'], $replace), 'body' => strtr($v['body'], $replace), 'locale' => $v['locale'],
                    'idempotency_key' => (string) Str::uuid(), 'available_at' => now()->addMinutes(intdiv($index, 5))]);
            }

            return $batch;
        });
    }
}
