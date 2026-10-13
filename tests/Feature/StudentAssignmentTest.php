<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Livewire\Users\StudentAddForm;
use App\Livewire\Users\StudentsTable;
use App\Livewire\Users\TeacherAddForm;
use App\Livewire\Users\TeachersTable;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class StudentAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private User $teacher;

    private User $otherTeacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['dashboard:access', 'users:view', 'user-type:teacher', 'users:student-add', 'users:student-delete', 'users:teacher-add', 'users:teacher-delete', 'users:teacher-findable'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->teacher = User::factory()->create();
        $this->otherTeacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->teacher->givePermissionTo(['dashboard:access', 'users:view', 'user-type:teacher', 'users:student-add', 'users:student-delete', 'users:teacher-add', 'users:teacher-delete']);
        $this->otherTeacher->givePermissionTo('user-type:teacher');
        $this->student->givePermissionTo('users:teacher-findable');
        $this->actingAs($this->teacher);
    }

    public function test_teacher_can_attach_and_remove_a_student_only_from_self(): void
    {
        Livewire::test(StudentAddForm::class, ['teacherId' => $this->teacher->id])
            ->set('userId', $this->student->id)->call('attach')->assertRedirect();
        $this->assertTrue($this->teacher->students()->where('users.id', $this->student->id)->exists());
        $table = new StudentsTable;
        $table->teacherId = $this->teacher->id;
        $table->delete($this->student);
        $this->assertFalse($this->teacher->students()->where('users.id', $this->student->id)->exists());
    }

    public function test_forged_teacher_id_and_non_findable_student_are_rejected(): void
    {
        Livewire::test(StudentAddForm::class, ['teacherId' => $this->teacher->id])
            ->set('userId', $this->student->id)->set('teacherId', $this->otherTeacher->id)
            ->call('attach')->assertForbidden();
        $other = User::factory()->create();
        Livewire::test(StudentAddForm::class, ['teacherId' => $this->teacher->id])
            ->set('userId', $other->id)->call('attach')->assertStatus(422);
        $this->assertDatabaseCount('teacher_student', 0);
    }

    public function test_teacher_cannot_add_or_remove_another_teacher_for_own_student(): void
    {
        $this->teacher->students()->attach($this->student);
        $this->otherTeacher->students()->attach($this->student);
        Livewire::test(TeacherAddForm::class, ['studentId' => $this->student->id])
            ->set('userId', $this->otherTeacher->id)->call('attach')->assertForbidden();
        $table = new TeachersTable;
        $table->studentId = $this->student->id;
        try {
            $table->delete($this->otherTeacher);
            $this->fail('Başka öğretmenin ilişkisi kaldırılamamalı.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('teacher_student', 2);
    }

    public function test_revoked_assignment_permission_blocks_an_open_form(): void
    {
        $component = Livewire::test(StudentAddForm::class, ['teacherId' => $this->teacher->id]);
        $this->teacher->revokePermissionTo('users:student-add');
        $component->set('userId', $this->student->id)->call('attach')->assertForbidden();
        $this->assertDatabaseCount('teacher_student', 0);
    }

    public function test_non_teacher_with_assignment_permission_can_assign_a_teacher(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo(['dashboard:access', 'users:view', 'users:teacher-add']);
        $this->actingAs($actor);
        Livewire::test(TeacherAddForm::class, ['studentId' => $this->student->id])
            ->set('userId', $this->otherTeacher->id)->call('attach')->assertRedirect();
        $this->assertTrue($this->otherTeacher->students()->where('users.id', $this->student->id)->exists());
    }

    public function test_teacher_can_open_only_own_assignment_tab_outside_students(): void
    {
        Language::firstOrCreate(['code' => 'nl'], ['name' => 'Nederlands', 'status' => StatusEnum::ACTIVE]);
        $this->get(route('admin.users.edit', ['user' => $this->teacher->id, 'tab' => 'teachers']))
            ->assertOk()->assertViewHas('activeTab', 'teachers');
        $this->get(route('admin.users.edit', ['user' => $this->teacher->id, 'tab' => 'password']))
            ->assertRedirect(route('admin.users.index'));
        $this->get(route('admin.users.edit', ['user' => $this->otherTeacher->id, 'tab' => 'teachers']))
            ->assertRedirect(route('admin.users.index'));
    }
}
