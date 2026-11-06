<?php

namespace Tests\Feature;

use App\Actions\Auth\ImpersonateUser;
use App\Actions\Auth\LogoutAction;
use App\Enums\StatusEnum;
use App\Livewire\Users\UserTable;
use App\Models\Language;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $student;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['tr', 'nl', 'en', 'fr'] as $locale) {
            Language::firstOrCreate(['code' => $locale], ['name' => $locale, 'status' => 'active']);
        }
        foreach (['dashboard:access', 'users:view', 'users:update', 'users:impersonate', 'user-type:admin', 'user-type:user', 'user-type:teacher'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        URL::forceRootUrl('http://localhost');
        URL::forceScheme('http');
        config(['app.url' => 'http://localhost', 'cache.default' => 'array']);
        $this->admin = User::factory()->create(['status' => StatusEnum::ACTIVE]);
        $this->admin->givePermissionTo(['dashboard:access', 'users:view', 'users:update', 'users:impersonate', 'user-type:admin']);
        $this->student = User::factory()->create(['status' => StatusEnum::ACTIVE]);
        $this->student->givePermissionTo('user-type:user');
        $this->teacher = User::factory()->create(['status' => StatusEnum::ACTIVE]);
        $this->teacher->givePermissionTo(['dashboard:access', 'users:view', 'user-type:teacher']);
        $this->actingAs($this->admin);
    }

    private function start(?User $user = null)
    {
        return $this->post(route('admin.users.impersonate', $user ?? $this->student));
    }

    public function test_admin_can_switch_to_student_and_return_with_new_sessions_without_changing_user_history(): void
    {
        $before = $this->student->fresh()->getRawOriginal();
        $id = session()->getId();
        $this->start()->assertRedirect(route('frontend.home'));
        $this->assertAuthenticatedAs($this->student);
        $this->assertNotSame($id, session()->getId());
        $state = session(ImpersonateUser::SESSION_KEY);
        $this->assertSame($this->admin->id, $state['actor_id']);
        $this->get(route('frontend.profile', ['lang' => 'tr']))->assertOk()->assertSee('header-account-return', false)
            ->assertDontSee('eduan-web-push-config', false);
        $this->post(route('frontend.impersonation.return'), ['return_token' => $state['token']])->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($this->admin);
        $this->assertFalse(session()->has(ImpersonateUser::SESSION_KEY));
        $this->assertSame($before, $this->student->fresh()->getRawOriginal());
        $this->assertSame(2, Log::whereIn('log_type', ['impersonation_started', 'impersonation_finished'])->count());
        $this->assertStringNotContainsString($state['token'], Log::where('log_type', 'impersonation_started')->latest('id')->value('data'));
        Http::assertNothingSent();
    }

    public function test_teacher_target_cannot_enter_dashboard_or_rebind_the_administrators_push_device(): void
    {
        settings()->set('notificationsEnabled', '1');
        settings()->save();
        $this->withSession(['webPushSubscriptionRecordId' => 1234]);
        $this->start($this->teacher)->assertRedirect();
        $this->assertNull(session('webPushSubscriptionRecordId'));
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('frontend.profile'))->assertOk()->assertDontSee(route('admin.home.index'), false);
        $this->get(route('frontend.notifications'))->assertOk()->assertSee(__('impersonation.device_preview'));
        $this->post(route('frontend.notifications.register'), [])->assertForbidden();
        $state = session(ImpersonateUser::SESSION_KEY);
        $this->post(route('frontend.impersonation.return'), ['return_token' => $state['token']])->assertRedirect();
        $this->assertSame(1234, session('webPushSubscriptionRecordId'));
        Http::assertNothingSent();
    }

    public static function prohibitedTargets(): array
    {
        return array_map(fn ($type) => [$type], ['self', 'admin', 'passive', 'deleted', 'untyped']);
    }

    #[DataProvider('prohibitedTargets')]
    public function test_ineligible_accounts_cannot_be_impersonated(string $type): void
    {
        $target = $this->student;
        if ($type === 'self') {
            $target = $this->admin;
        }
        if ($type === 'admin') {
            $target->givePermissionTo('user-type:admin');
        }
        if ($type === 'passive') {
            $target->update(['status' => StatusEnum::PASSIVE]);
        }
        if ($type === 'deleted') {
            $target->delete();
        }
        if ($type === 'untyped') {
            $target->revokePermissionTo('user-type:user');
        }
        $this->start($target)->assertStatus($type === 'deleted' ? 404 : 403);
        $this->assertAuthenticatedAs($this->admin);
        $this->assertFalse(session()->has(ImpersonateUser::SESSION_KEY));
    }

    public function test_permission_and_teacher_scope_are_enforced_even_when_button_is_bypassed(): void
    {
        $this->actingAs($this->teacher);
        $this->start()->assertForbidden();
        $this->teacher->givePermissionTo('users:impersonate');
        $this->start()->assertForbidden();
        $this->teacher->students()->attach($this->student);
        $this->start()->assertRedirect();
        $this->assertAuthenticatedAs($this->student);
    }

    public function test_switch_is_post_only_and_nested_switch_or_forged_return_is_rejected(): void
    {
        $this->get(route('admin.users.impersonate', $this->student))->assertStatus(405);
        $this->start($this->teacher)->assertRedirect();
        $this->start()->assertForbidden();
        $this->post(route('frontend.impersonation.return'), ['return_token' => str_repeat('x', 64)])->assertForbidden();
        $this->assertAuthenticatedAs($this->teacher);
        $this->get(route('frontend.impersonation.return'))->assertStatus(405);
    }

    public static function revokedSources(): array
    {
        return array_map(fn ($type) => [$type], ['permission', 'passive', 'deleted', 'password']);
    }

    #[DataProvider('revokedSources')]
    public function test_original_account_must_still_be_eligible_to_return(string $type): void
    {
        $this->start()->assertRedirect();
        $token = session(ImpersonateUser::SESSION_KEY.'.token');
        if ($type === 'permission') {
            $this->admin->revokePermissionTo('users:impersonate');
        }
        if ($type === 'passive') {
            $this->admin->update(['status' => StatusEnum::PASSIVE]);
        }
        if ($type === 'deleted') {
            $this->admin->delete();
        }
        if ($type === 'password') {
            $this->admin->update(['password' => bcrypt('ChangedPassword456!')]);
        }
        $this->post(route('frontend.impersonation.return'), ['return_token' => $token])->assertForbidden();
        $this->assertGuest();
        $this->assertFalse(session()->has(ImpersonateUser::SESSION_KEY));
    }

    public function test_revocation_ends_temporary_access_and_logout_does_not_leave_a_return_ticket(): void
    {
        $this->start()->assertRedirect();
        $this->admin->revokePermissionTo('users:impersonate');
        $this->get(route('frontend.profile'))->assertForbidden();
        $this->assertGuest();
        $this->admin->givePermissionTo('users:impersonate');
        $this->actingAs($this->admin);
        $this->start()->assertRedirect();
        LogoutAction::run();
        $this->assertGuest();
        $this->assertFalse(session()->has(ImpersonateUser::SESSION_KEY));
        Http::assertNothingSent();
    }

    public function test_user_detail_contains_confirmed_switch_on_the_left_only_with_permission_and_eligible_target(): void
    {
        $this->get(route('admin.users.index', ['lang' => 'tr']))->assertOk()->assertViewHas('title', 'Kullanıcılar');
        $component = Livewire::test(UserTable::class)->call('showDetail', $this->student->id)
            ->assertSee('data-account-switch', false);
        $html = $component->html();
        $this->assertLessThan(strpos($html, 'class="btn btn-warning"'), strpos($html, 'data-account-switch'));
        $this->admin->revokePermissionTo('users:impersonate');
        Livewire::test(UserTable::class)->call('showDetail', $this->student->id)->assertDontSee('data-account-switch', false);
    }

    public function test_permission_migration_grants_only_the_named_administrator_role_and_is_repeatable(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Yönetici', 'guard_name' => 'web']);
        $other = Role::firstOrCreate(['name' => 'Gent', 'guard_name' => 'web']);
        $teacher = Role::firstOrCreate(['name' => 'Öğretmen / Okul', 'guard_name' => 'web']);
        $otherBefore = $other->hasPermissionTo('users:impersonate');
        $teacherBefore = $teacher->hasPermissionTo('users:impersonate');
        $migration = require database_path('migrations/2026_10_30_100000_grant_user_impersonation_permission.php');
        $migration->up();
        $migration->up();
        $this->assertTrue($adminRole->fresh()->hasPermissionTo('users:impersonate'));
        $this->assertSame($otherBefore, $other->fresh()->hasPermissionTo('users:impersonate'));
        $this->assertSame($teacherBefore, $teacher->fresh()->hasPermissionTo('users:impersonate'));
    }

    public function test_start_and_return_routes_reject_missing_csrf_tokens(): void
    {
        // Keep the DB on testing while exercising the real non-testing CSRF branch.
        $this->assertSame('testing', DB::connection()->getDatabaseName());
        $this->app['env'] = 'local';
        $this->start()->assertStatus(419);
        $this->post(route('frontend.impersonation.return'), ['return_token' => str_repeat('x', 64)])->assertStatus(419);
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_cached_panel_livewire_snapshot_cannot_be_used_after_switching_to_a_teacher(): void
    {
        $html = $this->get(route('admin.users.index'))->assertOk()->getContent();
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        $snapshot = null;
        foreach ($matches[1] as $encoded) {
            $decoded = html_entity_decode($encoded, ENT_QUOTES, 'UTF-8');
            if ((json_decode($decoded, true)['memo']['name'] ?? '') === 'users.user-table') {
                $snapshot = $decoded;
                break;
            }
        }
        $this->assertNotNull($snapshot);
        $this->start($this->teacher)->assertRedirect();
        $this->postJson(Livewire::getUpdateUri(), ['components' => [[
            'snapshot' => $snapshot, 'updates' => new \stdClass,
            'calls' => [['method' => 'showDetail', 'params' => [$this->student->id]]],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
    }
}
