<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeTeacherAccess;
use App\Helpers\TableCache;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class StudentAddForm extends Component
{
    use CustomLivewireAlert;

    public int $teacherId;

    public int $userId;

    public function mount(int $teacherId = 0, int $userId = 0): void
    {
        AuthorizeTeacherAccess::run(User::findOrFail($teacherId), 'users:student-add');
        $this->teacherId = $teacherId;
        $this->userId = $userId;
    }

    public function attach()
    {
        AuthorizeTeacherAccess::run(User::findOrFail($this->teacherId), 'users:student-add');
        $student = User::findOrFail($this->userId);
        abort_unless($student->can('users:teacher-findable'), 422);
        if (empty($this->teacherId) || ! $teacher = User::find($this->teacherId)) {
            $this->message(__('Öğetmen seçimi yapılmadı!'))->error();

            return false;
        }

        if (empty($this->userId)) {
            $this->message(__('Kullanıcı seçimi yapınız!'))->error();

            return false;
        }

        if ($teacher->students()->find($this->userId)) {
            $this->message(__('Kullanıcı öğretmen ataması daha önce yapılmış!'))->error();

            return false;
        }

        $teacher->students()->attach($this->userId);
        TableCache::invalidate('user-students', 'user-teachers', 'options');

        return redirect()->route('admin.users.edit', ['user' => $teacher->id, 'tab' => 'teachers'])->with([
            'status' => 'success',
            'message' => __('Kullanıcı öğretmen ataması yapıldı!'),
        ]);
    }

    public function render()
    {
        return view('livewire.backend.users.student-add-form');
    }
}
