<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeTeacherAccess;
use App\Helpers\TableCache;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Locked;
use Livewire\Component;

class StudentsTable extends Component
{
    use CustomLivewireAlert;

    #[Locked]
    public int $teacherId;

    public function mount(int $teacherId): void
    {
        AuthorizeTeacherAccess::run(User::findOrFail($teacherId));
        $this->teacherId = $teacherId;
    }

    public function render()
    {
        AuthorizeTeacherAccess::run(User::findOrFail($this->teacherId));

        return view('livewire.backend.users.assignments-server-table', [
            'contextUserId' => $this->teacherId, 'table' => 'user-students',
            'endpoint' => 'admin.users.getStudentsData',
        ]);
    }

    public function delete(User $user)
    {
        $context = User::findOrFail($this->teacherId);
        AuthorizeTeacherAccess::run($context, 'users:student-delete');
        abort_unless($context->students()->where('users.id', $user->id)->exists(), 404);
        $context->students()->detach($user->id);
        TableCache::invalidate('user-students', 'user-teachers', 'options');
        $this->message(__('Öğrencinin/Kullanıcının öğretmen ataması/eşleşmesi kaldırıldı!'))->success();
        $this->dispatch('refresh-server-table', table: 'user-students');

        return true;
    }
}
