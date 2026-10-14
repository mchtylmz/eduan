<?php

namespace App\Actions\Communications;

use App\Helpers\WebPushSettings;
use App\Models\CommunicationDelivery;
use App\Models\User;
use Illuminate\Support\Str;

class RecordStudyNotification
{
    public static function run(User $student, string $kind, int $revision): void
    {
        $actor = auth()->user();
        if (! WebPushSettings::enabled() || ! $actor || $actor->id === $student->id) {
            return;
        }
        $locale = in_array(app()->getLocale(), ['tr', 'nl', 'en', 'fr'], true) ? app()->getLocale() : 'nl';
        CommunicationDelivery::firstOrCreate(['source_key' => 'study-'.$kind.'-'.$revision], [
            'actor_id' => $actor->id, 'user_id' => $student->id, 'channel' => 'push', 'title' => __('notifications.study_changed', [], $locale),
            'body' => __('notifications.changed_'.$kind, ['name' => trim($actor->name.' '.$actor->surname)], $locale), 'locale' => $locale,
            'path' => '/account/study', 'idempotency_key' => (string) Str::uuid(), 'available_at' => now(),
        ]);
    }
}
