<?php

namespace Tests\Concerns;

use App\Enums\StatusEnum;
use App\Helpers\WebPushSettings;
use App\Models\Language;
use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

trait UsesWebCommunications
{
    protected User $staff;

    protected User $student;

    protected User $outsider;

    protected string $pushApp = '11111111-1111-4111-8111-111111111111';

    protected string $pushId = '22222222-2222-4222-8222-222222222222';

    protected function setupCommunications(): void
    {
        $this->useStudyTimezone();
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['tr', 'nl', 'en', 'fr'] as $code) {
            Language::firstOrCreate(['code' => $code], ['name' => $code, 'status' => StatusEnum::ACTIVE]);
        }
        foreach (['dashboard:access', 'users:view', 'communications:view', 'communications:send', 'settings:view', 'settings:update', 'pages:view', 'pages:update', 'user-type:admin', 'user-type:teacher', 'user-type:user', 'study:access', 'study:view', 'study:update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->staff = User::factory()->create();
        $this->staff->givePermissionTo(['dashboard:access', 'users:view', 'communications:view', 'communications:send', 'user-type:teacher', 'study:access', 'study:view', 'study:update']);
        $this->student = User::factory()->create();
        $this->student->givePermissionTo(['study:access', 'user-type:user']);
        $this->outsider = User::factory()->create();
        $this->outsider->givePermissionTo(['study:access', 'user-type:user']);
        $this->staff->students()->attach($this->student);
        $this->actingAs($this->staff);
        URL::forceRootUrl('http://localhost');
        // A developer's HTTPS APP_URL must not change this HTTP localhost fixture.
        URL::forceScheme('http');
        config(['app.url' => 'http://localhost', 'queue.default' => 'database', 'cache.default' => 'array', 'mail.default' => 'array']);
        settings()->set(['notificationsEnabled' => '1', 'oneSignalLocalAppId' => $this->pushApp, 'oneSignalLocalApiKey' => Crypt::encryptString('test-key-never-returned'),
            'studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-10-01', 'mailHost' => 'test.invalid', 'mailFromEmail' => 'test@example.test']);
        settings()->save();
    }

    protected function fixtureSubscription(User $owner, ?string $id = null): WebPushSubscription
    {
        return WebPushSubscription::create(['user_id' => $owner->id, 'app_id' => $this->pushApp, 'subscription_id' => $id ?? $this->pushId, 'label' => 'QA Chrome',
            'consented_at' => now(), 'verified_at' => now()]);
    }

    protected function fakeProvider(User $owner, array $ids, int $status = 200): void
    {
        Http::fake([
            'https://api.onesignal.com/apps/*/users/by/external_id/*' => Http::response(['identity' => ['external_id' => WebPushSettings::externalId($owner)],
                'subscriptions' => array_map(fn ($id) => ['id' => $id, 'app_id' => $this->pushApp, 'type' => 'ChromePush', 'enabled' => true], $ids)], $status),
            'https://api.onesignal.com/apps/*/subscriptions/*' => Http::response([], 200),
            'https://api.onesignal.com/notifications' => Http::response(['id' => (string) Str::uuid()], 200),
        ]);
    }

    protected function batchInput(string $channel = 'email'): array
    {
        return ['recipients' => [$this->student->id], 'channel' => $channel, 'title' => 'Hello {{name}}', 'body' => 'Message {{surname}}', 'locale' => 'nl', 'submission_key' => (string) Str::uuid()];
    }
}
