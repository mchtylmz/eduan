<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Actions\Users\AuthorizeTeacherAccess;
use App\Helpers\TableCache;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class TeacherAddForm extends Component
{
    use CustomLivewireAlert;

    public int $studentId;

    public int $userId;

    public function mount(int $studentId = 0, int $userId = 0): void
    {
        AuthorizeStudentAccess::run(User::findOrFail($studentId), 'users:teacher-add');
        $this->studentId = $studentId;
        $this->userId = $userId;
    }

    public function attach()
    {
        AuthorizeStudentAccess::run(User::findOrFail($this->studentId), 'users:teacher-add');
        AuthorizeTeacherAccess::run(User::findOrFail($this->userId), 'users:teacher-add');
        if (empty($this->studentId) || ! $student = User::find($this->studentId)) {
            $this->message(__('Öğrenci/Kullanıcı seçimi yapılmadı!'))->error();

            return false;
        }

        if (empty($this->userId)) {
            $this->message(__('Kullanıcı seçimi yapınız!'))->error();

            return false;
        }

        if ($student->teachers()->find($this->userId)) {
            $this->message(__('Kullanıcı öğretmen ataması daha önce yapılmış!'))->error();

            return false;
        }

        $student->teachers()->attach($this->userId);
        TableCache::invalidate('user-students', 'user-teachers', 'options');

        return redirect()->route('admin.users.edit', ['user' => $student->id, 'tab' => 'teachers'])->with([
            'status' => 'success',
            'message' => __('Kullanıcı öğretmen ataması yapıldı!'),
        ]);
    }

    public function render()
    {
        return view('livewire.backend.users.teacher-add-form');
    }
}
