<?php

namespace Tests\Feature;

use App\Actions\Roles\CreateOrUpdateAction;
use App\Actions\Users\UpdateRoleAction;
use App\Enums\RoleTypeEnum;
use App\Enums\SettingsTabsEnum;
use App\Enums\StatusEnum;
use App\Livewire\Roles\RoleTable;
use App\Livewire\Settings\LogDetailTable;
use App\Livewire\Settings\LogsTable;
use App\Models\Language;
use App\Models\Log;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsMenuTableTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        Queue::fake();
        $permissions = ['dashboard:access', 'roles:view', 'roles:add', 'roles:update', 'roles:delete', 'settings:view', 'settings:update', 'seasons:view', 'seasons:update', 'seasons:delete'];
        foreach ([...$permissions, ...RoleTypeEnum::values()] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->actor = User::factory()->create();
        $this->actor->givePermissionTo($permissions);
        $this->actingAs($this->actor)->withSession(['locale' => 'nl']);
    }

    public function test_menu_lists_and_season_cards_render_without_rappasoft(): void
    {
        Season::create(['name' => 'Menu term', 'start_date' => '2040-01-01', 'end_date' => '2040-06-01', 'status' => 'active']);
        $this->get(route('admin.roles.index'))->assertOk()->assertSee('roles-table');
        $this->get(route('admin.settings.logs'))->assertOk()->assertSee('logs-table')->assertSee('data-table-filters', false);
        $this->get(route('admin.seasons.index'))->assertOk()->assertSee('admin-season-card')->assertSee('season-start_date-0');
    }

    public function test_all_thirteen_settings_tabs_keep_existing_forms_and_controls(): void
    {
        foreach (SettingsTabsEnum::cases() as $tab) {
            $this->get(route('admin.settings.index', ['activeTab' => $tab->value]))->assertOk()
                ->assertSee('data-settings-tab="'.$tab->value.'"', false)->assertSee('admin-settings-nav');
        }
        $this->get(route('admin.settings.index', ['activeTab' => 'general']))->assertSee('name="settings[appName]"', false);
        $this->get(route('admin.settings.index', ['activeTab' => 'popup']))->assertSee('settings[popupContent_nl]', false);
        $this->get(route('admin.settings.index', ['activeTab' => 'logo']))->assertSee('dropify', false);
        $this->get(route('admin.roles.create'))->assertOk()->assertSee('name="permissions[]"', false);
    }

    public function test_role_json_pagination_counts_and_permission_group(): void
    {
        $a = Role::create(['name' => 'menu-role-a']);
        $b = Role::create(['name' => 'menu-role-b']);
        $a->givePermissionTo(RoleTypeEnum::TEACHER->value);
        User::factory()->create()->assignRole($a);
        $params = ['search' => 'menu-role-', 'sort' => 'name', 'limit' => 1];
        $this->getJson(route('admin.roles.getData', $params))->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('rows.0.id', $a->id)->assertJsonPath('rows.0.users_count', 1)
            ->assertJsonPath('rows.0.group', RoleTypeEnum::TEACHER->name());
        $this->getJson(route('admin.roles.getData', [...$params, 'offset' => 1]))->assertJsonPath('rows.0.id', $b->id);
        $this->getJson(route('admin.roles.getData', [...$params, 'sort' => 'users_count', 'order' => 'desc']))->assertJsonPath('rows.0.id', $a->id);
    }

    public function test_log_filters_default_order_and_private_payload_exclusion(): void
    {
        $base = ['user_id' => $this->actor->id, 'log_date' => '2026-10-06 10:00:00', 'table_name' => 'menu-audit', 'data_id' => $this->actor->id,
            'data' => '{"private":"hidden"}', 'agent' => 'menu-agent', 'browser' => 'Chrome', 'ip' => '127.0.0.1', 'device' => 'desktop'];
        $first = Log::create([...$base, 'log_type' => 'create']);
        $last = Log::create([...$base, 'log_type' => 'edit']);
        $params = ['search' => 'menu-agent', 'limit' => 1];
        $this->getJson(route('admin.settings.getLogsData', $params))->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $last->id);
        $this->getJson(route('admin.settings.getLogsData', [...$params, 'offset' => 1]))->assertJsonPath('rows.0.id', $first->id);
        $response = $this->getJson(route('admin.settings.getLogsData', [...$params, 'users' => [$this->actor->id], 'log_type' => 'create']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $first->id);
        $this->assertArrayNotHasKey('data', $response->json('rows.0'));
        $this->assertFalse($response->json('rows.0.can_delete'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        foreach (['name', 'surname', 'log_type', 'agent', 'browser', 'ip', 'log_date'] as $sort) {
            $this->getJson(route('admin.settings.getLogsData', ['search' => 'menu-agent', 'sort' => $sort]))->assertOk();
        }
    }

    public function test_invalid_requests_and_revoked_permissions_are_rejected_before_cached_data(): void
    {
        $this->getJson(route('admin.roles.getData', ['sort' => 'permissions; drop']))->assertUnprocessable();
        $this->getJson(route('admin.settings.getLogsData', ['log_type' => 'invalid']))->assertUnprocessable();
        $this->getJson(route('admin.settings.getLogsData', ['users' => [99999999]]))->assertUnprocessable();
        $this->getJson(route('admin.roles.getData'))->assertOk();
        $this->getJson(route('admin.settings.getLogsData'))->assertOk();
        $this->actor->revokePermissionTo(['roles:view', 'settings:view']);
        $this->getJson(route('admin.roles.getData'))->assertForbidden();
        $this->getJson(route('admin.settings.getLogsData'))->assertForbidden();
        Livewire::test(RoleTable::class)->assertForbidden();
        Livewire::test(LogsTable::class)->assertForbidden();
        auth()->logout();
        $this->getJson(route('admin.roles.getData'))->assertUnauthorized();
    }

    public function test_role_permission_and_assignment_updates_invalidate_new_list(): void
    {
        CreateOrUpdateAction::run('menu-cache', [RoleTypeEnum::USER->value]);
        $role = Role::where('name', 'menu-cache')->firstOrFail();
        $params = ['search' => 'menu-cache'];
        $this->getJson(route('admin.roles.getData', $params))->assertJsonPath('rows.0.users_count', 0);
        UpdateRoleAction::run(User::factory()->create(), $role->id);
        $this->getJson(route('admin.roles.getData', $params))->assertJsonPath('rows.0.users_count', 1);
        CreateOrUpdateAction::run('menu-cache', [RoleTypeEnum::TEACHER->value], $role);
        $this->getJson(route('admin.roles.getData', $params))->assertJsonPath('rows.0.group', RoleTypeEnum::TEACHER->name());
    }

    public function test_log_comparison_aligns_old_and_current_fields_and_marks_changes(): void
    {
        $role = Role::create(['name' => 'Current comparison name']);
        $log = Log::create(['user_id' => $this->actor->id, 'log_date' => '2026-10-06 10:00:00',
            'log_type' => 'edit', 'table_name' => 'roles', 'data_id' => $role->id,
            'data' => json_encode(['id' => $role->id, 'name' => 'Previous comparison name', 'guard_name' => 'web'])]);
        Livewire::test(LogsTable::class)->call('showDetail', $log->id)
            ->assertSee('Previous comparison name')->assertSee('Current comparison name')
            ->assertSee('data-log-changes-toggle', false)->assertSee('data-log-changed="true"', false)
            ->assertSee('data-log-changed="false"', false)->assertSee('admin-log-meta');
    }

    public function test_log_secrets_are_masked_in_html_state_and_public_method_returns(): void
    {
        $base = ['user_id' => $this->actor->id, 'log_date' => '2026-10-06 10:00:00',
            'log_type' => 'edit', 'table_name' => 'users', 'data_id' => $this->actor->id];
        $old = ['name' => 'Visible', 'password' => 'private-old', 'remember_token' => 'private-token-old'];
        $current = ['name' => 'Visible', 'password' => 'private-new', 'remember_token' => 'private-token-new'];
        $first = Log::create([...$base, 'data' => json_encode($old)]);
        $next = Log::create([...$base, 'data' => json_encode($current)]);
        $panel = Livewire::test(LogDetailTable::class, ['logId' => $first->id])
            ->assertSee('***')->assertSee('password')->assertSee('Visible')->assertDontSee('private-');
        $this->assertStringNotContainsString('private-', json_encode($panel->snapshot));
        $panel->call('jsonData')->assertReturned(['name' => 'Visible', 'password' => '***', 'remember_token' => '***']);
        $panel->call('currentData')->assertReturned(['name' => 'Visible', 'password' => '***', 'remember_token' => '***']);
        $panel->call('diff', 'password')->assertReturned(true);
        $fallback = Livewire::test(LogDetailTable::class, ['logId' => $next->id]);
        $this->assertSame('***', $fallback->get('currentData.password'));
        $fallback->call('currentData');
        $this->assertStringNotContainsString($this->actor->getRawOriginal('password'), json_encode([$fallback->snapshot, $fallback->effects]));
    }

    public function test_readonly_detail_and_role_delete_permission_are_rechecked(): void
    {
        $role = Role::create(['name' => 'menu-detail']);
        Livewire::test(RoleTable::class)->call('showDetail', $role->id)->assertSet('detailId', $role->id)->assertSee('menu-detail');
        $log = Log::create(['user_id' => $this->actor->id, 'log_date' => '2026-10-06 10:00:00', 'log_type' => 'create', 'data' => '{"name":"log-test"}']);
        Livewire::test(LogsTable::class)->call('showDetail', $log->id)->assertDispatched('open-table-detail', table: 'logs')->assertSee('log-test');
        $this->actor->revokePermissionTo('roles:delete');
        Livewire::test(RoleTable::class)->call('delete', $role->id)->assertForbidden();
        $this->assertModelExists($role);
    }
}
