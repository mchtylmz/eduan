<?php

namespace App\Livewire\Frontend\Auth;

use App\Actions\Users\UpdatePasswordAction;
use App\Mail\CreatePasswordMail;
use App\Mail\RecoverPasswordMail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class CreatePassword extends Component
{
    use CustomLivewireAlert;

    public User $user;

    public string $password;
    public string $password_confirmation;
    public string $passwordInputType = 'password';

    public function changeType(): void
    {
        $this->passwordInputType = $this->passwordInputType == 'text' ? 'password' : 'text';
    }

    public function rules(): array
    {
        return [
            'password' => 'required|string|min:3|confirmed',
            'password_confirmation' => 'required|string|min:3',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'password' => __('Yeni Parolanız'),
            'password_confirmation' => __('Tekrar Yeni Parolanız'),
        ];
    }

    public function update()
    {
        $this->validate();

        UpdatePasswordAction::run(user: $this->user, password: $this->password);
        $this->sendCreatePasswordMail();

        return redirect()->route('login')->with([
           'status' => 'success',
           'message' => __('Yeni parolanız başarıyla güncellendi')
        ]);
    }

    public function sendCreatePasswordMail(): void
    {
        Mail::to($this->user->email)
            ->send(new CreatePasswordMail(user: $this->user, locale: app()->getLocale()));
    }

    public function render()
    {
        return view('livewire.frontend.auth.create-password');
    }
}
