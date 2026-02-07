<?php

namespace App\Livewire\Users;

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
        $this->studentId = $studentId;
        $this->userId = $userId;
    }

    public function attach()
    {
        if (empty($this->studentId) || !$student = User::find($this->studentId)) {
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

        return redirect()->route('admin.users.edit', ['user' => $student->id, 'tab' => 'teachers'])->with([
            'status' => 'success',
            'message' => __('Kullanıcı öğretmen ataması yapıldı!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.users.teacher-add-form');
    }
}
