<?php

namespace Tests\Feature;

use App\Enums\PageMenuEnum;
use App\Helpers\WebPushSettings;
use App\Models\CommunicationDelivery;
use App\Models\Page;
use App\Models\WebPushSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesStudyTimezone;
use Tests\Concerns\UsesWebCommunications;
use Tests\TestCase;

class WebNotificationsTest extends TestCase
{
    use DatabaseTransactions, UsesStudyTimezone, UsesWebCommunications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCommunications();
    }

    public function test_notifications_page_widget_and_private_context_are_authenticated(): void
    {
        $this->actingAs($this->student);
        $response = $this->get(route('frontend.notifications'))->assertOk()->assertSee('data-web-push-page', false)->assertSee('float-notifications', false)->assertSee('noindex, nofollow', false);
        $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'));
        $this->get(route('frontend.profile'))->assertOk()->assertDontSee('member-nav-copy">Meldingen', false);
        $this->getJson(route('frontend.notifications.context'))->assertOk()->assertJsonPath('config.notifyButton.enable', false)->assertJsonPath('config.welcomeNotification.disable', true)
            ->assertJsonPath('config.serviceWorkerPath', 'push/onesignal/OneSignalSDKWorker.js')->assertDontSee('test-key-never-returned');
        auth()->logout();
        $this->get(route('frontend.notifications'))->assertRedirect();
        $this->get(route('frontend.home'))->assertOk()->assertDontSee('float-notifications', false)->assertDontSee('eduan-web-push-config', false);
    }

    public function test_explanation_is_managed_in_pages_and_not_publicly_accessible(): void
    {
        $page = Page::where('menu', PageMenuEnum::NOTIFICATIONS)->firstOrFail();
        $page->setTranslation('content', 'nl', '<p>Updated page explanation</p>');
        $page->save();
        $this->get(route('frontend.notifications', ['lang' => 'nl']))->assertSee('Updated page explanation');
        $this->get(route('frontend.page', $page->slug))->assertNotFound();
        $this->staff->givePermissionTo(['user-type:admin', 'settings:view', 'settings:update', 'pages:view', 'pages:update']);
        $this->get(route('admin.settings.index', ['activeTab' => 'notifications']))->assertOk()->assertSee(route('admin.pages.edit', $page), false);
    }

    public function test_production_app_is_never_used_as_local_fallback(): void
    {
        settings()->set('oneSignalLocalAppId', '');
        settings()->save();
        $this->assertFalse(WebPushSettings::ready());
        $this->getJson(route('frontend.notifications.context'))->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_wrong_origin_is_rejected_without_provider_request(): void
    {
        config(['app.url' => 'http://localhost:9999']);
        $this->getJson(route('frontend.notifications.context'))->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_register_validated_subscription_idempotently_and_profile_link_appears(): void
    {
        $this->actingAs($this->student);
        $this->fakeProvider($this->student, [$this->pushId]);
        $input = ['subscription_id' => $this->pushId, 'label' => 'Chrome', 'consent' => true, 'explicit_consent' => true];
        $a = $this->postJson(route('frontend.notifications.register'), $input)->assertOk()->json('id');
        $b = $this->postJson(route('frontend.notifications.register'), $input)->assertOk()->json('id');
        $this->assertSame($a, $b);
        $this->assertSame(1, WebPushSubscription::where('user_id', $this->student->id)->count());
        $this->get(route('frontend.profile'))->assertSee('member-nav-copy">Meldingen', false);
        $this->assertSame($a, session('webPushSubscriptionRecordId'));
    }

    public function test_foreign_provider_subscription_cannot_be_bound(): void
    {
        $this->actingAs($this->student);
        $this->fakeProvider($this->student, []);
        $this->postJson(route('frontend.notifications.register'), ['subscription_id' => $this->pushId, 'label' => 'Chrome', 'consent' => true, 'explicit_consent' => true])->assertUnprocessable();
        $this->assertDatabaseMissing('web_push_subscriptions', ['subscription_id' => $this->pushId]);
    }

    public function test_wrong_provider_identity_is_rejected_without_binding(): void
    {
        $this->actingAs($this->student);
        $this->fakeProvider($this->outsider, [$this->pushId]);
        $this->postJson(route('frontend.notifications.register'), ['subscription_id' => $this->pushId, 'label' => 'Chrome', 'consent' => true, 'explicit_consent' => true])->assertStatus(503);
        $this->assertDatabaseMissing('web_push_subscriptions', ['subscription_id' => $this->pushId]);
    }

    public function test_consent_missing_and_invalid_uuid_are_rejected_before_network(): void
    {
        foreach ([['subscription_id' => 'bad', 'label' => 'Chrome', 'consent' => true, 'explicit_consent' => true], ['subscription_id' => $this->pushId, 'label' => 'Chrome', 'consent' => false, 'explicit_consent' => true]] as $input) {
            $this->postJson(route('frontend.notifications.register'), $input)->assertUnprocessable();
        }
        Http::assertNothingSent();
    }

    public function test_revoked_subscription_cannot_silently_restore_consent(): void
    {
        $this->actingAs($this->student);
        $row = $this->fixtureSubscription($this->student);
        $row->update(['revoked_at' => now()]);
        $this->fakeProvider($this->student, [$this->pushId]);
        $input = ['subscription_id' => $this->pushId, 'label' => 'Chrome', 'consent' => true, 'explicit_consent' => false];
        $this->postJson(route('frontend.notifications.register'), $input)->assertConflict();
        $this->postJson(route('frontend.notifications.register'), array_replace($input, ['explicit_consent' => true]))->assertOk();
        $this->assertNull($row->fresh()->revoked_at);
    }

    public function test_revoke_is_owner_scoped_and_disables_only_the_selected_device(): void
    {
        $a = $this->fixtureSubscription($this->student);
        $b = $this->fixtureSubscription($this->student, (string) Str::uuid());
        $this->actingAs($this->outsider);
        $this->post(route('frontend.notifications.revoke', $a->id))->assertNotFound();
        $this->actingAs($this->student);
        $this->fakeProvider($this->student, [$this->pushId, $b->subscription_id]);
        $this->post(route('frontend.notifications.revoke', $a->id))->assertRedirect();
        $this->assertNotNull($a->fresh()->revoked_at);
        $this->assertNull($b->fresh()->revoked_at);
        Http::assertSent(fn ($r) => $r->method() === 'PATCH' && str_ends_with($r->url(), $this->pushId) && $r['subscription']['enabled'] === false);
    }

    public function test_provider_error_still_stops_local_targeting_without_leaking_details(): void
    {
        $this->actingAs($this->student);
        $row = $this->fixtureSubscription($this->student);
        $this->fakeProvider($this->student, [$this->pushId], 503);
        $this->post(route('frontend.notifications.revoke', $row->id))->assertRedirect()->assertSessionHas('notificationMessage');
        $this->assertNotNull($row->fresh()->revoked_at);
        $this->get(route('frontend.notifications'))->assertOk()->assertSee(__('notifications.retry_revoke'))->assertSee(__('notifications.revoke_pending'));
    }

    public function test_last_ten_sent_push_messages_only_are_personal(): void
    {
        foreach (range(1, 12) as $i) {
            CommunicationDelivery::create(['user_id' => $this->student->id, 'channel' => 'push', 'title' => 'Own '.$i, 'body' => 'Body '.$i, 'locale' => 'nl', 'status' => 'sent', 'idempotency_key' => (string) Str::uuid(), 'available_at' => now(), 'completed_at' => now()]);
        }
        CommunicationDelivery::create(['user_id' => $this->outsider->id, 'channel' => 'push', 'title' => 'Other secret', 'body' => 'private', 'locale' => 'nl', 'status' => 'sent', 'idempotency_key' => (string) Str::uuid(), 'available_at' => now()]);
        $this->actingAs($this->student);
        $this->get(route('frontend.notifications'))->assertSee('Own 12')->assertSee('Own 3')->assertDontSee('Own 2<', false)->assertDontSee('Other secret');
    }

    public function test_keys_are_encrypted_and_blank_input_preserves_saved_value(): void
    {
        $this->staff->givePermissionTo(['user-type:admin', 'settings:view', 'settings:update']);
        $this->postJson(route('admin.settings.store'), ['settings' => ['oneSignalApiKey' => 'secret-live-key']])->assertOk();
        $encrypted = settings()->oneSignalApiKey;
        $this->assertNotSame('secret-live-key', $encrypted);
        $this->assertSame('secret-live-key', Crypt::decryptString($encrypted));
        $this->postJson(route('admin.settings.store'), ['settings' => ['oneSignalApiKey' => '']])->assertOk();
        $this->assertSame($encrypted, settings()->oneSignalApiKey);
        $this->get(route('admin.settings.index', ['activeTab' => 'notifications']))->assertOk()->assertDontSee('secret-live-key')->assertDontSee($encrypted);
        $this->staff->revokePermissionTo('user-type:admin');
        $this->postJson(route('admin.settings.store'), ['settings' => ['notificationsEnabled' => '1']])->assertForbidden();
    }

    public function test_logout_revokes_current_device_but_not_other_browsers(): void
    {
        $this->actingAs($this->student);
        $a = $this->fixtureSubscription($this->student);
        $b = $this->fixtureSubscription($this->student, (string) Str::uuid());
        $this->fakeProvider($this->student, [$a->subscription_id, $b->subscription_id]);
        $this->withSession(['webPushSubscriptionRecordId' => $a->id])->get(route('frontend.logout'))->assertRedirect();
        $this->assertNotNull($a->fresh()->revoked_at);
        $this->assertNull($b->fresh()->revoked_at);
    }

    public function test_settings_reject_unknown_keys_invalid_ids_and_non_origin_urls(): void
    {
        $this->staff->givePermissionTo(['user-type:admin', 'settings:view', 'settings:update']);
        foreach ([['oneSignalUnknown' => 'x'], ['oneSignalAppId' => 'not-a-uuid'], ['oneSignalOrigin' => 'https://example.test/path'], ['oneSignalOrigin' => 'https://example.test?token=x']] as $input) {
            $this->postJson(route('admin.settings.store'), ['settings' => $input])->assertUnprocessable();
        }
        $this->assertSame($this->pushApp, settings()->oneSignalLocalAppId);
        Http::assertNothingSent();
    }

    public function test_redesigned_page_omits_provider_name_in_all_localized_explanations_without_overwriting_page(): void
    {
        $this->actingAs($this->student);
        $page = Page::where('menu', PageMenuEnum::NOTIFICATIONS)->firstOrFail();
        $before = $page->getRawOriginal('content');
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            $this->get(route('frontend.notifications', ['lang' => $locale]))->assertOk()
                ->assertSee('notifications-preferences', false)->assertSee('data-push-badge', false)->assertDontSee('OneSignal');
        }
        $this->assertSame($before, $page->fresh()->getRawOriginal('content'));
        Http::assertNothingSent();
    }

    public function test_custom_explanation_html_is_preserved_while_provider_label_is_neutral(): void
    {
        $page = Page::where('menu', PageMenuEnum::NOTIFICATIONS)->firstOrFail();
        $page->setTranslation('content', 'nl', '<p><strong>Custom explanation</strong>: oneSIGNAL</p>');
        $page->save();
        $this->get(route('frontend.notifications', ['lang' => 'nl']))->assertOk()
            ->assertSee('<strong>Custom explanation</strong>', false)->assertSee('de meldingsdienst')->assertDontSee('oneSIGNAL');
        $this->assertStringContainsString('oneSIGNAL', $page->fresh()->getTranslation('content', 'nl'));
    }

    public function test_personal_history_precedes_devices_and_settings_are_a_compact_disclosure_in_each_language(): void
    {
        $this->actingAs($this->student);
        $own = $this->fixtureSubscription($this->student);
        $foreign = $this->fixtureSubscription($this->outsider, (string) Str::uuid());
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            $response = $this->get(route('frontend.notifications', ['lang' => $locale]))->assertOk()
                ->assertSee('<details class="notifications-settings" data-push-setup>', false)
                ->assertSee('data-push-onboarding', false)->assertSee('data-push-active-help hidden', false)
                ->assertSee('data-push-consent', false)->assertSee('data-push-check', false)
                ->assertSee(__('notifications.start_title', [], $locale))
                ->assertSee(__('notifications.step_consent', [], $locale))
                ->assertSee(__('notifications.step_permission', [], $locale))
                ->assertSee('notifications-enable-steps', false)
                ->assertSee(__('study.back_account', [], $locale))
                ->assertSee('data-push-device="'.$own->subscription_id.'"', false)
                ->assertDontSee($foreign->subscription_id);
            $this->assertLessThan(strpos($response->getContent(), 'aria-labelledby="notifications-devices-title"'), strpos($response->getContent(), 'aria-labelledby="notifications-history-title"'));
            $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'));
        }
        Http::assertNothingSent();
    }

    public function test_not_configured_page_disables_consent_and_permission_without_sdk_request(): void
    {
        settings()->set('notificationsEnabled', '0');
        settings()->save();
        $this->get(route('frontend.notifications'))->assertOk()->assertSee('data-push-consent disabled', false)
            ->assertSee('data-push-enable disabled', false)->assertSee('data-state="unavailable"', false);
        Http::assertNothingSent();
    }
}
