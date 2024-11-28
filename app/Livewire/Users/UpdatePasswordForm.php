<?php

namespace App\Livewire\Users;

use App\Actions\Users\UpdatePasswordAction;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class UpdatePasswordForm extends Component
{
    use CustomLivewireAlert;

    public User $user;
    public string $newPassword;
    public string $newPasswordConfirmation;

    public function rules(): array
    {
        return [
            'newPassword' => ['required', 'string', 'min:6'],
            'newPasswordConfirmation' => ['required', 'string', 'min:6', 'same:newPassword'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'newPassword' => __('Yeni Parola'),
            'newPasswordConfirmation' => __('Tekrar Yeni Parola')
        ];
    }

    public function save()
    {
        $this->validate();

        if (auth()->user()->cannot('users:update-password')) {
            $this->message(__('Kullanıcı parolası güncellenemez, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        UpdatePasswordAction::run(user: $this->user, password: $this->newPassword);

        return redirect()->route('admin.users.index')->with([
            'status' => 'success',
            'message' => __('Kullanıcı parolası güncellendi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.users.update-password-form');
    }
}
