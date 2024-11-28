<?php

namespace App\Livewire\Frontend\Auth;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\RecoverPasswordAction;
use App\Mail\EmailVerificationMail;
use App\Mail\RecoverPasswordMail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class RecoverPassword extends Component
{
    use CustomLivewireAlert;

    public string $username;
    public string $message;

    public function rules(): array
    {
        return [
            'username' => 'required|string|min:3'
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'username' => __('E-posta Adresiniz')
        ];
    }

    public function submit()
    {
        $this->validate();

        $user = RecoverPasswordAction::run(username: $this->username);
        if (!$user) {
            $this->message(__('E-posta adresi için kullanıcı bulunamadı!'))->error();
            return false;
        }

        $this->sendRecoverPasswordMail($user);

        $this->message = __('E-posta adresi için parola sıfırlama bağlantısı ve talimatları e-posta olarak gönderildi.');
        return false;
    }

    public function sendRecoverPasswordMail(User $user): void
    {
        Mail::to($user->email)
            ->send(new RecoverPasswordMail(user: $user, locale: app()->getLocale()));
    }

    public function render()
    {
        return view('livewire.frontend.auth.recover-password');
    }
}
