<?php

namespace App\Livewire\Frontend\Auth;

use App\Actions\Auth\LoginAction;
use App\Traits\CustomLivewireAlert;
use App\Traits\VerifiesTurnstile;
use Livewire\Component;

class Login extends Component
{
    use CustomLivewireAlert, VerifiesTurnstile;

    public string $username;

    public string $password;

    public bool $remember = false;

    public string $passwordInputType = 'password';

    public function changeType(): void
    {
        $this->passwordInputType = $this->passwordInputType == 'text' ? 'password' : 'text';
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|min:3',
            'password' => 'required|string|min:3',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'username' => __('E-posta Adresiniz'),
            'password' => __('Parola'),
        ];
    }

    public function login()
    {
        $this->verifyTurnstile('login');
        $this->validate();

        $isLogin = LoginAction::run(
            username: $this->username,
            password: $this->password,
            remember: $this->remember
        );
        if (! $isLogin) {
            $this->reset('password');

            $this->message(__('Kullanıcı adı / parola hatalı!'))->error();

            return false;
        }

        return redirect()->route('frontend.profile');
    }

    public function render()
    {
        return view('livewire.frontend.auth.login');
    }
}
