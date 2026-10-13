<?php

namespace Tests\Feature;

use App\Enums\RoleTypeEnum;
use App\Enums\StatusEnum;
use App\Jobs\UpdateSeasonIdForResults;
use App\Livewire\Languages\LanguageForm;
use App\Livewire\Languages\LanguageTable;
use App\Livewire\Roles\RoleTable;
use App\Livewire\Season\SeasonForm;
use App\Models\Language;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PanelModuleCrudTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $user = User::factory()->create();
        $permissions = ['dashboard:access'];
        foreach (['languages', 'roles', 'seasons'] as $resource) {
            foreach (['view', 'add', 'update', 'delete'] as $operation) {
                $permissions[] = "$resource:$operation";
            }
        }
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        foreach (RoleTypeEnum::cases() as $type) {
            Permission::findOrCreate($type->value, 'web');
        }
        $user->givePermissionTo($permissions);
        $this->actingAs($user);
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        settings()->set('defaultLocale', 'nl');
        settings()->save();
    }

    public function test_language_create_edit_delete_and_default_language_guard(): void
    {
        $this->assertFileDoesNotExist(lang_path('qx.json'));
        Livewire::test(LanguageForm::class, ['lazy' => false])->set('code', 'qx')->set('name', 'Test language')
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $language = Language::where('code', 'qx')->firstOrFail();
        Livewire::test(LanguageForm::class, ['languageId' => $language->id, 'lazy' => false])
            ->assertSet('code', 'qx')->set('name', 'Edited language')->call('save')->assertRedirect();
        $this->assertSame('Edited language', $language->fresh()->name);
        $this->assertFalse((new LanguageTable)->delete(Language::where('code', 'nl')->firstOrFail()));
        $this->assertTrue((new LanguageTable)->delete($language));
        $this->assertModelMissing($language);
    }

    public function test_duplicate_language_code_is_rejected(): void
    {
        $initialLanguageCount = Language::count();
        Livewire::test(LanguageForm::class, ['lazy' => false])->set('code', 'nl')->set('name', 'Duplicate')
            ->call('save')->assertHasErrors('code');
        $this->assertSame($initialLanguageCount, Language::count());
    }

    public function test_role_create_edit_sync_permissions_and_guard_assigned_role_deletion(): void
    {
        $this->postJson(route('admin.roles.store'), ['name' => 'test-reviewer', 'permissions' => ['languages:view']])
            ->assertOk()->assertJsonPath('redirect', route('admin.roles.index'));
        $role = Role::where('name', 'test-reviewer')->firstOrFail();
        $this->get(route('admin.roles.edit', $role))->assertOk();
        $this->postJson(route('admin.roles.update', $role), ['name' => 'edited-reviewer', 'permissions' => ['roles:view']])->assertOk();
        $this->assertSame('edited-reviewer', $role->fresh()->name);
        $this->assertSame(['roles:view'], $role->fresh()->permissions->pluck('name')->all());
        $student = User::factory()->create();
        $student->assignRole($role);
        $this->assertFalse((new RoleTable)->delete($role));
        $student->removeRole($role);
        $this->assertTrue((new RoleTable)->delete($role));
        $this->assertModelMissing($role);
    }

    public function test_role_write_requires_permission_and_valid_input(): void
    {
        $initialRoleCount = Role::count();
        $this->postJson(route('admin.roles.store'), ['name' => '', 'permissions' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'permissions']);
        auth()->user()->revokePermissionTo('roles:add');
        $this->postJson(route('admin.roles.store'), ['name' => 'blocked', 'permissions' => ['roles:view']])->assertForbidden();
        $this->assertSame($initialRoleCount, Role::count());
    }

    public function test_season_create_edit_delete_and_background_dispatch(): void
    {
        $form = Livewire::test(SeasonForm::class)->set('seasons', [[
            'id' => 0, 'name' => 'Term', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'active',
        ]])->call('save')->assertHasNoErrors();
        $season = Season::where('name', 'Term')->firstOrFail();
        $form->set('seasons.0.name', 'Edited term')->call('save')->assertHasNoErrors();
        $this->assertSame('Edited term', $season->fresh()->name);
        Queue::assertPushed(UpdateSeasonIdForResults::class, 2);
        $form->call('delete', 0);
        $this->assertModelMissing($season);
    }

    public function test_season_rejects_invalid_dates_and_duplicate_boundaries(): void
    {
        $initialSeasonCount = Season::count();
        Livewire::test(SeasonForm::class)->set('seasons', [[
            'id' => 0, 'name' => 'Invalid', 'start_date' => '2026-06-30', 'end_date' => '2026-01-01', 'status' => 'active',
        ]])->call('save')->assertHasErrors('seasons.0.end_date');
        $season = ['id' => 0, 'name' => 'Duplicate', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'active'];
        Livewire::test(SeasonForm::class)->set('seasons', [$season, $season])->call('save');
        $this->assertSame($initialSeasonCount, Season::count());
        Queue::assertNothingPushed();
    }
}
