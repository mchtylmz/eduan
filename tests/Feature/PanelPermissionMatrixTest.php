<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Blogs\BlogForm;
use App\Livewire\Exams\ExamForm;
use App\Livewire\Lessons\LessonForm;
use App\Livewire\Pages\PageForm;
use App\Livewire\Questions\QuestionForm;
use App\Livewire\Tests\TestForm;
use App\Livewire\Topics\TopicForm;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelPermissionMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::spy();
        Mail::fake();
        Queue::fake();
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        settings()->set(['defaultLocale' => 'nl', 'examlanguageCode' => 'nl', 'testlanguageCode' => 'nl']);
        settings()->save();
        Livewire::withoutLazyLoading();
    }

    private function user(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public static function resources(): array
    {
        $rows = [];
        foreach (['contacts', 'newsletter', 'blogs', 'lessons', 'topics', 'questions', 'exams', 'tests', 'users',
            'roles', 'stats', 'settings', 'languages', 'ai', 'leagues', 'seasons'] as $resource) {
            $rows[$resource] = ["admin.$resource.index", "$resource:view"];
        }
        $rows['pages'] = ['admin.pages.all', 'pages:view'];
        $rows['faqs'] = ['admin.pages.faqs.index', 'pages:view'];

        return $rows;
    }

    #[DataProvider('resources')]
    public function test_panel_entry_requires_both_dashboard_and_resource_permission(string $route, string $permission): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
        $this->actingAs($this->user([$permission]))->get(route($route))->assertForbidden();
        $this->actingAs($this->user(['dashboard:access']))->get(route($route))->assertForbidden();
        $this->actingAs($this->user(['dashboard:access', $permission]))->get(route($route))->assertOk();
    }

    public static function unguardedForms(): array
    {
        return [
            'lesson-create' => ['lesson', false], 'lesson-update' => ['lesson', true],
            'topic-create' => ['topic', false], 'topic-update' => ['topic', true],
            'page-create' => ['page', false], 'page-update' => ['page', true],
        ];
    }

    #[DataProvider('unguardedForms')]
    public function test_view_permission_does_not_allow_a_direct_form_write(string $type, bool $edit): void
    {
        $this->actingAs($this->user(['dashboard:access', "{$type}s:view"]));
        $lesson = Lesson::create(['code' => 'matrix-lesson', 'name' => ['nl' => 'Original']]);
        if ($type === 'lesson') {
            $form = Livewire::test(LessonForm::class, ['lessonId' => $edit ? $lesson->id : 0]);
            $form->set('name', ['nl' => 'Changed'])->set('code', $edit ? 'matrix-lesson' : 'forbidden-lesson');
        } elseif ($type === 'topic') {
            $topic = Topic::create(['lesson_id' => $lesson->id, 'code' => 'matrix-topic', 'title' => ['nl' => 'Original']]);
            $form = Livewire::test(TopicForm::class, ['topicId' => $edit ? $topic->id : 0]);
            $form->set('lesson_id', $lesson->id)->set('title', ['nl' => 'Changed'])->set('code', $edit ? 'matrix-topic' : 'forbidden-topic');
        } else {
            $page = Page::create(['slug' => 'matrix-page', 'title' => ['nl' => 'Original'], 'content' => ['nl' => 'Body']]);
            $form = Livewire::test(PageForm::class, ['pageId' => $edit ? $page->id : 0]);
            $form->set('title', ['nl' => 'Changed'])->set('slug', $edit ? 'matrix-page' : 'forbidden-page');
        }
        $form->call('save');
        if ($edit) {
            $value = match ($type) {
                'lesson' => $lesson->fresh()->getTranslation('name', 'nl'),
                'topic' => $topic->fresh()->getTranslation('title', 'nl'),
                'page' => $page->fresh()->getTranslation('title', 'nl'),
            };
            $this->assertSame('Original', $value, 'Görüntüleme izni mevcut kaydı değiştirememeli.');
        } else {
            $this->assertDatabaseMissing("{$type}s", [$type === 'page' ? 'slug' : 'code' => "forbidden-$type"]);
        }
        $form->assertForbidden();
    }

    public static function writableForms(): array
    {
        return [
            'lesson' => [LessonForm::class, Lesson::class, 'lessons', 'name', 'code', 'lessonId'],
            'topic' => [TopicForm::class, Topic::class, 'topics', 'title', 'code', 'topicId'],
            'page' => [PageForm::class, Page::class, 'pages', 'title', 'slug', 'pageId'],
        ];
    }

    #[DataProvider('writableForms')]
    public function test_authorized_writes_work_and_permission_changes_are_rechecked(string $class, string $model, string $resource, string $title, string $key, string $parameter): void
    {
        $actor = $this->user(['dashboard:access', "$resource:view", "$resource:add", "$resource:update"]);
        $this->actingAs($actor);
        $lesson = Lesson::create(['code' => 'control-parent', 'name' => ['nl' => 'Parent']]);
        $form = Livewire::test($class, ['lazy' => false])->set($title, ['nl' => 'Created'])->set($key, 'permission-control');
        if ($resource === 'topics') {
            $form->set('lesson_id', $lesson->id);
        }
        $form->call('save')->assertHasNoErrors()->assertRedirect();
        $record = $model::where($key, 'permission-control')->firstOrFail();
        Livewire::test($class, [$parameter => $record->id, 'lazy' => false])->set($title, ['nl' => 'Updated'])
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame('Updated', $record->fresh()->getTranslation($title, 'nl'));

        $opened = Livewire::test($class, [$parameter => $record->id, 'lazy' => false]);
        $actor->revokePermissionTo("$resource:update");
        $opened->set('permission', "$resource:add")->set($title, ['nl' => 'Forbidden'])
            ->call('save')->assertForbidden();
        $this->assertSame('Updated', $record->fresh()->getTranslation($title, 'nl'));

        $actor->revokePermissionTo("$resource:add");
        $actor->givePermissionTo("$resource:update");
        Livewire::test($class, ['lazy' => false])->set('permission', "$resource:update")->call('save')->assertForbidden();

        $actor->givePermissionTo("$resource:add");
        $opened = Livewire::test($class, [$parameter => $record->id, 'lazy' => false]);
        $actor->revokePermissionTo('dashboard:access');
        $opened->call('save')->assertForbidden();
        $this->assertSame('Updated', $record->fresh()->getTranslation($title, 'nl'));
    }

    public static function guardedForms(): array
    {
        return [
            'question' => [QuestionForm::class, 'questions:view'],
            'practice' => [ExamForm::class, 'exams:view'],
            'timed' => [TestForm::class, 'tests:view'],
            'blog' => [BlogForm::class, 'blogs:view'],
        ];
    }

    #[DataProvider('guardedForms')]
    public function test_other_forms_reject_view_only_direct_save(string $class, string $permission): void
    {
        $this->actingAs($this->user(['dashboard:access', $permission]));
        Livewire::test($class)->call('save')->assertForbidden();
    }

    public static function studentTabs(): array
    {
        return array_map(fn ($tab) => [$tab], ['user', 'favorite', 'tests', 'exams', 'topicsNotResult', 'topicsStats']);
    }

    #[DataProvider('studentTabs')]
    public function test_teacher_detail_tabs_allow_only_own_students(string $tab): void
    {
        $teacher = $this->user(['dashboard:access', 'users:view', 'users:update', 'stats:view', 'user-type:user']);
        Permission::findOrCreate('user-type:teacher', 'web');
        $role = Role::create(['name' => 'Teacher matrix '.fake()->uuid(), 'guard_name' => 'web']);
        $role->givePermissionTo('user-type:teacher');
        $teacher->assignRole($role);
        (require database_path('migrations/2026_10_07_160000_grant_teacher_result_view_permissions.php'))->up();
        $teacher->unsetRelation('roles');
        $student = User::factory()->create();
        $other = User::factory()->create();
        $teacher->students()->attach($student);
        $this->actingAs($teacher)->get(route('admin.users.edit', ['user' => $student->id, 'tab' => $tab]))->assertOk();
        $this->get(route('admin.users.edit', ['user' => $other->id, 'tab' => $tab]))->assertRedirect(route('admin.users.index'));
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
    }
}
