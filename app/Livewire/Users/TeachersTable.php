<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Actions\Users\AuthorizeTeacherAccess;
use App\Helpers\TableCache;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TeachersTable extends Component
{
    use CustomLivewireAlert;

    #[Locked]
    public int $studentId;

    public function mount(int $studentId): void
    {
        AuthorizeStudentAccess::run(User::findOrFail($studentId));
        $this->studentId = $studentId;
    }

    public function render()
    {
        AuthorizeStudentAccess::run(User::findOrFail($this->studentId));

        return view('livewire.backend.users.assignments-server-table', [
            'contextUserId' => $this->studentId, 'table' => 'user-teachers',
            'endpoint' => 'admin.users.getTeachersData',
        ]);
    }

    public function delete(User $user)
    {
        $context = User::findOrFail($this->studentId);
        AuthorizeStudentAccess::run($context, 'users:teacher-delete');
        AuthorizeTeacherAccess::run($user, 'users:teacher-delete');
        abort_unless($context->teachers()->where('users.id', $user->id)->exists(), 404);
        $context->teachers()->detach($user->id);
        TableCache::invalidate('user-students', 'user-teachers', 'options');
        $this->message(__('Öğrencinin/Kullanıcının öğretmen ataması/eşleşmesi kaldırıldı!'))->success();
        $this->dispatch('refresh-server-table', table: 'user-teachers');

        return true;
    }
}
