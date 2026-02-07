<?php

namespace App\Livewire\Users;

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
        $this->teacherId = $teacherId;
        $this->userId = $userId;
    }

    public function attach()
    {
        if (empty($this->teacherId) || !$teacher = User::find($this->teacherId)) {
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

        return redirect()->route('admin.users.edit', ['user' => $teacher->id, 'tab' => 'teachers'])->with([
            'status' => 'success',
            'message' => __('Kullanıcı öğretmen ataması yapıldı!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.users.student-add-form');
    }
}
