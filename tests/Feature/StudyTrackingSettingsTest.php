<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Helpers\StudyTrackingSettings;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudyTrackingSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        app()->setLocale('nl');
        config(['app.timezone' => 'Europe/Brussels']);
        $this->travelTo(now()->setDateTime(2026, 10, 14, 12, 0, 0));
        foreach (['dashboard:access', 'settings:view', 'settings:update', 'user-type:admin', 'user-type:teacher'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo(['dashboard:access', 'settings:view', 'settings:update', 'user-type:admin']);
        $this->actingAs($this->actor)->withSession(['locale' => 'nl']);
        settings()->set(['studyTrackingEnabled' => '0', 'studyTrackingStartedOn' => '', 'studyAllowPastActivities' => '0', 'studyMinimumTopicQuestions' => '10']);
        settings()->save();
    }

    private function save(array $values)
    {
        return $this->postJson(route('admin.settings.store'), ['settings' => $values]);
    }

    public function test_defaults_render_localized_form_and_do_not_activate_the_module(): void
    {
        $this->get(route('admin.settings.index', ['activeTab' => 'study']))->assertOk()
            ->assertSee('Studieopvolging')->assertSee('name="settings[studyTrackingEnabled]"', false)
            ->assertSee('name="settings[studyTrackingStartedOn]"', false)->assertSee('value="10"', false)
            ->assertSee('Europe/Brussels');
        $this->assertFalse(StudyTrackingSettings::enabled());
        $this->assertNull(StudyTrackingSettings::startedOn());
        $this->assertFalse(StudyTrackingSettings::allowPastActivities());
        $this->assertSame(10, StudyTrackingSettings::minimumTopicQuestions());
    }

    public function test_first_activation_assigns_today_and_reactivation_never_resets_date(): void
    {
        $this->save(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => ''])->assertOk();
        $this->assertSame('2026-10-14', StudyTrackingSettings::startedOn());
        $this->save(['studyTrackingEnabled' => '0', 'studyTrackingStartedOn' => ''])->assertOk();
        $this->travelTo(now()->addDays(3));
        $this->save(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => ''])->assertOk();
        $this->assertSame('2026-10-14', StudyTrackingSettings::startedOn());
    }

    public function test_missing_settings_have_safe_defaults_and_unrelated_saves_do_not_activate(): void
    {
        settings()->set(['studyTrackingEnabled' => null, 'studyTrackingStartedOn' => null,
            'studyAllowPastActivities' => null, 'studyMinimumTopicQuestions' => null]);
        settings()->save();
        $this->assertFalse(StudyTrackingSettings::enabled());
        $this->assertNull(StudyTrackingSettings::startedOn());
        $this->assertFalse(StudyTrackingSettings::allowPastActivities());
        $this->assertSame(10, StudyTrackingSettings::minimumTopicQuestions());
        $this->save(['siteTitle_nl' => 'Unrelated settings update'])->assertOk();
        $this->assertFalse(StudyTrackingSettings::enabled());
        $this->assertNull(StudyTrackingSettings::startedOn());
    }

    public function test_enabled_only_save_assigns_start_and_disabled_date_changes_do_not_activate(): void
    {
        $this->save(['studyTrackingStartedOn' => '2026-09-01'])->assertOk();
        $this->assertFalse(StudyTrackingSettings::enabled());
        $this->save(['studyTrackingEnabled' => '1'])->assertOk();
        $this->assertSame('2026-09-01', StudyTrackingSettings::startedOn());
    }

    public function test_admin_can_set_an_explicit_past_date_and_controls_without_overwriting_other_settings(): void
    {
        settings()->set('siteTitle_nl', 'Existing title');
        settings()->save();
        $this->save(['studyTrackingEnabled' => '1', 'studyTrackingStartedOn' => '2026-09-01',
            'studyAllowPastActivities' => '1', 'studyMinimumTopicQuestions' => '20'])->assertOk();
        $this->assertSame('2026-09-01', StudyTrackingSettings::startedOn());
        $this->assertTrue(StudyTrackingSettings::allowPastActivities());
        $this->assertSame(20, StudyTrackingSettings::minimumTopicQuestions());
        $this->assertSame('Existing title', settings()->siteTitle_nl);
    }

    public static function invalidValues(): array
    {
        return [
            [['studyTrackingEnabled' => 'active'], 'studyTrackingEnabled'],
            [['studyAllowPastActivities' => '2'], 'studyAllowPastActivities'],
            [['studyTrackingStartedOn' => '2026-02-30'], 'studyTrackingStartedOn'],
            [['studyTrackingStartedOn' => '2026-10-15'], 'studyTrackingStartedOn'],
            [['studyTrackingStartedOn' => 'tomorrow'], 'studyTrackingStartedOn'],
            [['studyMinimumTopicQuestions' => '0'], 'studyMinimumTopicQuestions'],
            [['studyMinimumTopicQuestions' => '-1'], 'studyMinimumTopicQuestions'],
            [['studyMinimumTopicQuestions' => '1.5'], 'studyMinimumTopicQuestions'],
            [['studyMinimumTopicQuestions' => '4294967296'], 'studyMinimumTopicQuestions'],
            [['studyUnexpected' => '1'], 'studyUnexpected'],
        ];
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_settings_do_not_write_any_study_settings(array $bad, string $field): void
    {
        $this->save(['studyTrackingEnabled' => '1', ...$bad])->assertUnprocessable()->assertJsonValidationErrors('settings.'.$field);
        $this->assertFalse(StudyTrackingSettings::enabled());
        $this->assertNull(StudyTrackingSettings::startedOn());
    }

    public function test_teacher_cannot_read_or_forge_study_settings_but_existing_settings_still_work(): void
    {
        $this->actor->revokePermissionTo('user-type:admin');
        $this->actor->givePermissionTo('user-type:teacher');
        $this->get(route('admin.settings.index', ['activeTab' => 'study']))->assertForbidden();
        $this->get(route('admin.settings.index', ['activeTab' => 'general']))->assertOk()->assertDontSee('activeTab=study');
        $this->save(['studyTrackingEnabled' => '1'])->assertForbidden();
        $this->save(['siteTitle_nl' => 'Allowed existing setting'])->assertOk();
        $this->assertFalse(StudyTrackingSettings::enabled());
        $this->assertSame('Allowed existing setting', settings()->siteTitle_nl);
    }

    public function test_setting_update_permission_and_guest_access_are_enforced(): void
    {
        $this->actor->revokePermissionTo('settings:update');
        $this->save(['studyTrackingEnabled' => '1'])->assertForbidden();
        auth()->logout();
        $this->save(['studyTrackingEnabled' => '1'])->assertUnauthorized();
    }
}
