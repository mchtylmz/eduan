<?php

namespace Tests\Feature;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Livewire\Users\FavoriteTable;
use App\Livewire\Users\UpdatePasswordForm;
use App\Livewire\Users\UserForm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StudentAccessTest extends TestCase
{
    use DatabaseTransactions;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();
        foreach (['dashboard:access', 'users:view', 'users:update', 'users:update-password', 'user-type:teacher', 'user-type:user'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->teacher->givePermissionTo(['dashboard:access', 'users:view', 'users:update', 'users:update-password', 'user-type:teacher']);
        $this->teacher->students()->attach($this->student);
        $this->actingAs($this->teacher);
    }

    public function test_teacher_cannot_open_an_unrelated_student_form_password_or_favorites(): void
    {
        $other = User::factory()->create();
        Livewire::test(UserForm::class, ['user' => $other, 'lazy' => false])->assertForbidden();
        Livewire::test(UpdatePasswordForm::class, ['user' => $other])->assertForbidden();
        Livewire::test(FavoriteTable::class, ['userId' => $other->id, 'lazy' => false])->assertForbidden();
    }

    public function test_teacher_can_update_own_student_password(): void
    {
        Livewire::test(UpdatePasswordForm::class, ['user' => $this->student])
            ->set('newPassword', 'new-password')->set('newPasswordConfirmation', 'new-password')
            ->call('save')->assertRedirect(route('admin.users.index'));
        $this->assertTrue(Hash::check('new-password', $this->student->fresh()->password));
    }

    public function test_removing_relationship_after_opening_form_prevents_password_write(): void
    {
        $component = Livewire::test(UpdatePasswordForm::class, ['user' => $this->student]);
        $this->teacher->students()->detach($this->student);
        $component->set('newPassword', 'new-password')->set('newPasswordConfirmation', 'new-password')
            ->call('save')->assertForbidden();
        $this->assertTrue(Hash::check('admin', $this->student->fresh()->password));
    }

    public function test_teacher_cannot_assign_a_role_outside_the_allowed_student_roles(): void
    {
        $role = Role::create(['name' => 'Restricted role', 'guard_name' => 'web']);
        Livewire::test(UserForm::class, ['user' => $this->student->fresh(), 'lazy' => false])
            ->set('role_id', $role->id)->call('save')->assertForbidden();
        $this->assertFalse($this->student->fresh()->hasRole($role));
    }

    public function test_non_teacher_with_required_permissions_can_access_other_students(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['dashboard:access', 'users:view']);
        $this->actingAs($actor);
        AuthorizeStudentAccess::run($this->student);
        $this->assertAuthenticatedAs($actor);
    }
}
