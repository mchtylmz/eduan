<?php

namespace App\Livewire\Frontend\Account;

use App\Actions\Users\UpdatePasswordAction;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class UpdatePasswordForm extends Component
{
    use CustomLivewireAlert;

    public User $user;

    public string $current_password;
    public string $password;
    public string $password_confirmation;
    public string $passwordInputType = 'password';

    public function mount(): void
    {
        $this->user = User::find(auth()->id());
    }

    public function changeType(): void
    {
        $this->passwordInputType = $this->passwordInputType == 'text' ? 'password' : 'text';
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string|min:3|current_password:web',
            'password' => 'required|string|min:3|confirmed',
            'password_confirmation' => 'required|string|min:3',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'current_password' => __('Şu Anki Parolanız'),
            'password' => __('Yeni Parolanız'),
            'password_confirmation' => __('Tekrar Yeni Parolanız'),
        ];
    }

    public function submit()
    {
        $this->validate();

        UpdatePasswordAction::run(
            user: $this->user,
            password: $this->password
        );

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->message(__('Yeni parolanız güncellendi'))->success();
    }

    public function render()
    {
        return view('livewire.frontend.account.update-password-form');
    }
}
