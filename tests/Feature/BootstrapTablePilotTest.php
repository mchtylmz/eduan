<?php

namespace Tests\Feature;

use App\Actions\Users\UpdateRoleAction;
use App\Enums\RoleTypeEnum;
use App\Enums\StatusEnum;
use App\Helpers\TableCache;
use App\Livewire\Lessons\LessonTable;
use App\Livewire\Topics\TopicTable;
use App\Livewire\Users\UserTable;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BootstrapTablePilotTest extends TestCase
{
    use DatabaseTransactions;

    private User $actor;

    private Lesson $lesson;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('nl');
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'status' => StatusEnum::ACTIVE]);
        $this->actor = User::factory()->create();
        $permissions = ['dashboard:access', RoleTypeEnum::TEACHER->value, RoleTypeEnum::USER->value];
        foreach (['lessons', 'topics', 'users'] as $resource) {
            foreach (['view', 'update', 'delete'] as $operation) {
                $permissions[] = $resource.':'.$operation;
            }
        }
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->actor->givePermissionTo(array_diff($permissions, [RoleTypeEnum::TEACHER->value, RoleTypeEnum::USER->value]));
        $this->actingAs($this->actor);
        $this->withSession(['locale' => 'nl']);
        $this->lesson = Lesson::create(['code' => 'pilot-math', 'name' => ['nl' => 'Pilot Wiskunde', 'en' => 'Pilot Mathematics'], 'sort' => 1, 'status' => StatusEnum::ACTIVE]);
        $this->topic = Topic::create(['lesson_id' => $this->lesson->id, 'code' => 'pilot-topic', 'title' => ['nl' => 'Pilot onderwerp', 'en' => 'Pilot topic'], 'status' => StatusEnum::ACTIVE]);
    }

    public function test_three_pages_render_server_tables_filters_and_local_assets(): void
    {
        foreach (['lessons', 'topics', 'users'] as $resource) {
            $html = $this->get(route('admin.'.$resource.'.index'))->assertOk()
                ->assertSee('data-side-pagination="server"', false)
                ->assertSee('data-table-filters', false)->assertSee($resource.'-detail')->getContent();
            preg_match_all('~(?:src|href)="[^"]*(/backend/assets/[^"?]+)~', $html, $matches);
            foreach ($matches[1] as $path) {
                $this->assertFileExists(public_path($path));
            }
        }
    }

    public function test_new_table_labels_are_translated_in_all_three_interface_languages(): void
    {
        foreach (['nl', 'en', 'tr'] as $locale) {
            foreach (['lesson_detail', 'topic_detail', 'user_detail', 'load_error', 'reset_filters', 'close', 'date_from', 'date_to', 'email', 'student_count', 'teacher_count', 'read_only'] as $key) {
                $this->assertNotSame('tables.'.$key, trans('tables.'.$key, [], $locale));
            }
        }
    }

    public function test_json_endpoints_require_dashboard_and_resource_permission_even_after_warming_cache(): void
    {
        foreach (['lessons', 'topics', 'users'] as $resource) {
            $this->actingAs($this->actor)->getJson(route('admin.'.$resource.'.getData'))->assertOk();
            $this->actor->revokePermissionTo($resource.':view');
            $this->getJson(route('admin.'.$resource.'.getData'))->assertForbidden();
        }
        $this->actor->revokePermissionTo('dashboard:access');
        $this->getJson(route('admin.lessons.getData'))->assertForbidden();
        auth()->logout();
        $this->getJson(route('admin.lessons.getData'))->assertUnauthorized();
    }

    public function test_lesson_pagination_sort_locale_status_and_relationship_counts(): void
    {
        $other = Lesson::create(['code' => 'pilot-z', 'name' => ['nl' => 'Pilot Z'], 'status' => StatusEnum::PASSIVE]);
        Question::create(['lesson_id' => $this->lesson->id, 'topic_id' => $this->topic->id, 'title' => 'Question', 'locale' => 'nl']);
        $parameters = ['search' => 'pilot-', 'sort' => 'code', 'order' => 'desc', 'limit' => 1];
        $this->getJson(route('admin.lessons.getData', $parameters))->assertOk()
            ->assertJsonPath('total', 2)->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.id', $other->id);
        $this->getJson(route('admin.lessons.getData', [...$parameters, 'offset' => 1]))->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('rows.0.id', $this->lesson->id)
            ->assertJsonPath('rows.0.topics_count', 1)->assertJsonPath('rows.0.questions_count', 1);
        $this->getJson(route('admin.lessons.getData', ['search' => 'pilot-', 'locale' => 'en', 'status' => 'active']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $this->lesson->id);
        foreach (['name', 'topics_count', 'questions_count', 'sort', 'hits', 'status'] as $sort) {
            $this->getJson(route('admin.lessons.getData', ['search' => 'pilot-', 'sort' => $sort]))->assertOk();
        }
    }

    public function test_topic_filters_translation_and_parent_sort_work(): void
    {
        $this->getJson(route('admin.topics.getData', ['lessons' => [$this->lesson->id], 'locale' => 'en', 'status' => 'active', 'sort' => 'lesson_name']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.title', 'Pilot onderwerp')
            ->assertJsonPath('rows.0.lesson_name', 'Pilot Wiskunde');
        $this->getJson(route('admin.topics.getData', ['search' => 'Pilot Wiskunde']))->assertOk()->assertJsonPath('total', 1);
        foreach (['title', 'questions_count', 'sort', 'hits', 'status'] as $sort) {
            $this->getJson(route('admin.topics.getData', ['search' => 'pilot-', 'sort' => $sort]))->assertOk();
        }
    }

    public function test_invalid_pagination_sort_filters_and_dates_are_rejected(): void
    {
        foreach ([['limit' => 101], ['offset' => -1], ['sort' => 'name; DROP TABLE users'], ['search' => str_repeat('x', 251)], ['status' => 'invalid'], ['locale' => 'missing']] as $parameters) {
            $this->getJson(route('admin.lessons.getData', $parameters))->assertUnprocessable();
        }
        $this->getJson(route('admin.topics.getData', ['lessons' => [999999999]]))->assertUnprocessable();
        foreach ([['roles' => ['missing-role']], ['email_verified' => [3]], ['registered_from' => '2026-10-06', 'registered_to' => '2026-10-01']] as $parameters) {
            $this->getJson(route('admin.users.getData', $parameters))->assertUnprocessable();
        }
    }

    public function test_user_role_verification_and_inclusive_date_filters(): void
    {
        $role = Role::findOrCreate('pilot-reader', 'web');
        $user = User::factory()->create(['username' => 'pilot-user', 'last_login_at' => '2026-10-06 23:59:59', 'email_verified' => 0]);
        $user->assignRole($role);
        $parameters = ['search' => 'pilot-user', 'roles' => [$role->name], 'email_verified' => [0], 'last_login_from' => '2026-10-06', 'last_login_to' => '2026-10-06'];
        $this->getJson(route('admin.users.getData', $parameters))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $user->id);
        $this->getJson(route('admin.users.getData', ['search' => 'pilot-user', 'last_login_to' => '2026-10-05']))->assertOk()->assertJsonPath('total', 0);
        $this->getJson(route('admin.users.getData', ['search' => 'pilot-user', 'registered_to' => '2100-01-01']))->assertOk()->assertJsonPath('total', 1);
    }

    public function test_teacher_json_scope_remains_correct_after_student_assignment_changes(): void
    {
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $own = User::factory()->create(['username' => 'pilot-own']);
        $other = User::factory()->create(['username' => 'pilot-other']);
        $this->actor->students()->attach($own);
        $parameters = ['search' => 'pilot-'];
        $this->getJson(route('admin.users.getData', $parameters))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $own->id);
        $this->getJson(route('admin.users.getData', ['search' => $other->username]))->assertOk()->assertJsonPath('total', 0);
        $this->actor->students()->detach($own);
        $this->actor->students()->attach($other);
        $this->getJson(route('admin.users.getData', $parameters))->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.id', $other->id);
    }

    public function test_user_json_does_not_expose_password_tokens_or_private_metadata(): void
    {
        $response = $this->getJson(route('admin.users.getData', ['search' => $this->actor->username]))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $row = $response->json('rows.0');
        foreach (['password', 'remember_token', 'email', 'meta'] as $key) {
            $this->assertArrayNotHasKey($key, $row);
        }
        $this->assertFalse($row['can_delete']);
    }

    public function test_cache_hit_refresh_and_model_update_return_correct_values(): void
    {
        $parameters = ['search' => $this->lesson->code];
        $this->getJson(route('admin.lessons.getData', $parameters))->assertJsonPath('rows.0.hits', 0);
        DB::table('lessons')->where('id', $this->lesson->id)->update(['hits' => 7]);
        $this->getJson(route('admin.lessons.getData', $parameters))->assertJsonPath('rows.0.hits', 0);
        $this->getJson(route('admin.lessons.getData', [...$parameters, 'refresh' => 1]))->assertJsonPath('rows.0.hits', 7);
        $this->lesson->update(['hits' => 9]);
        $this->getJson(route('admin.lessons.getData', $parameters))->assertJsonPath('rows.0.hits', 9);
    }

    public function test_role_assignment_invalidates_cached_user_rows(): void
    {
        $user = User::factory()->create(['username' => 'pilot-role-user']);
        $role = Role::findOrCreate('pilot-role', 'web');
        $parameters = ['search' => $user->username];
        $this->getJson(route('admin.users.getData', $parameters))->assertJsonPath('rows.0.roles', []);
        UpdateRoleAction::run($user, $role->id);
        $this->getJson(route('admin.users.getData', $parameters))->assertJsonPath('rows.0.roles', [$role->name]);
    }

    public function test_cache_separates_actor_locale_and_table_and_targeted_invalidation_keeps_other_entries(): void
    {
        $other = User::factory()->create();
        $calls = 0;
        $load = function () use (&$calls) {
            return ['value' => ++$calls];
        };
        $this->assertSame(['value' => 1], TableCache::remember('lessons', [], $load));
        $this->assertSame(['value' => 1], TableCache::remember('lessons', [], $load));
        $this->assertSame(['value' => 2], TableCache::remember('users', [], $load));
        TableCache::invalidate('lessons');
        $this->assertSame(['value' => 3], TableCache::remember('lessons', [], $load));
        $this->assertSame(['value' => 2], TableCache::remember('users', [], $load));
        app()->setLocale('en');
        $this->assertSame(['value' => 4], TableCache::remember('lessons', [], $load));
        $this->actingAs($other);
        $this->assertSame(['value' => 5], TableCache::remember('lessons', [], $load));
    }

    public function test_details_open_and_user_details_recheck_teacher_scope(): void
    {
        Livewire::test(LessonTable::class)->call('showDetail', $this->lesson->id)->assertSet('detailId', $this->lesson->id)
            ->assertSee('Pilot Wiskunde')->assertDispatched('open-table-detail');
        Livewire::test(TopicTable::class)->call('showDetail', $this->topic->id)->assertSee('Pilot onderwerp');
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        $student = User::factory()->create();
        $this->actor->students()->attach($student);
        $panel = Livewire::test(UserTable::class)->call('showDetail', $student->id)->assertSet('detailId', $student->id);
        $this->actor->students()->detach($student);
        $panel->call('$refresh')->assertNotFound();
        Livewire::test(UserTable::class)->call('showDetail', $student->id)->assertNotFound();
    }

    public function test_user_detail_metrics_follow_account_type(): void
    {
        Permission::findOrCreate(RoleTypeEnum::ADMIN->value, 'web');
        foreach (RoleTypeEnum::cases() as $type) {
            $subject = User::factory()->create();
            $subject->givePermissionTo($type->value);
            $subject->students()->attach(User::factory()->create());
            $subject->teachers()->attach(User::factory()->create());
            $expected = match ($type) {
                RoleTypeEnum::ADMIN => [],
                RoleTypeEnum::TEACHER => [__('tables.student_count') => 1],
                RoleTypeEnum::USER => [__('tables.teacher_count') => 1],
            };
            Livewire::test(UserTable::class)->call('showDetail', $subject->id)
                ->assertViewHas('metrics', $expected)
                ->assertViewHas('details', fn ($details) => array_key_exists(__('tables.student_count'), $details) === ($type === RoleTypeEnum::TEACHER)
                    && array_key_exists(__('tables.teacher_count'), $details) === ($type === RoleTypeEnum::USER));
        }
        $this->get(route('admin.users.index'))->assertOk()->assertSee('data-user-card-view', false)->assertSee('data-user-cards', false);
    }

    public function test_detail_id_cannot_be_forged_from_client_state(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(UserTable::class)->set('detailId', $this->actor->id);
    }

    public function test_delete_preserves_relationship_guards_and_refreshes_successful_table(): void
    {
        Livewire::test(LessonTable::class)->call('delete', $this->lesson->id);
        $this->assertModelExists($this->lesson);
        Question::create(['lesson_id' => $this->lesson->id, 'topic_id' => $this->topic->id, 'title' => 'Question', 'locale' => 'nl']);
        Livewire::test(TopicTable::class)->call('delete', $this->topic->id);
        $this->assertModelExists($this->topic);
        $empty = Lesson::create(['code' => 'pilot-empty', 'name' => ['nl' => 'Empty']]);
        Livewire::test(LessonTable::class)->call('delete', $empty->id)->assertDispatched('refresh-server-table');
        $this->assertSoftDeleted($empty);
    }

    public function test_deletes_recheck_permission_and_teacher_scope_and_cannot_remove_self(): void
    {
        $this->actor->revokePermissionTo('lessons:delete');
        Livewire::test(LessonTable::class)->call('delete', $this->lesson->id)->assertForbidden();
        $this->assertModelExists($this->lesson);
        Livewire::test(UserTable::class)->call('delete', $this->actor->id);
        $this->assertModelExists($this->actor);
        $other = User::factory()->create();
        $this->actor->givePermissionTo(RoleTypeEnum::TEACHER->value);
        Livewire::test(UserTable::class)->call('delete', $other->id)->assertForbidden();
        $this->assertModelExists($other);
    }
}
