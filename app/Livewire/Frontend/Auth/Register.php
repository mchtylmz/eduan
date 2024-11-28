<?php

namespace App\Livewire\Frontend\Auth;

use App\Actions\Users\CreateOrUpdateUserAction;
use App\Actions\Users\UpdatePasswordAction;
use App\Actions\Users\UpdateRoleAction;
use App\Enums\StatusEnum;
use App\Mail\EmailVerificationMail;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class Register extends Component
{
    use CustomLivewireAlert;

    public bool $afterRegister = false;
    public string $name;
    public string $surname;
    public string $email;
    public string $phone;
    public string $password;
    public string $password_confirmation;
    public bool $acceptTerms = true;
    public string $passwordInputType = 'password';

    public function changeType(): void
    {
        $this->passwordInputType = $this->passwordInputType == 'text' ? 'password' : 'text';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'surname' => 'required|string|min:2',
            'email' => [
                'required', 'string', 'min:3', 'email:rfc,dns',
                Rule::unique('users', 'username')->ignore($this->email ?? ''),
            ],
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
            'acceptTerms' => ['required', 'accepted'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'email' => __('E-posta Adresiniz'),
            'name' => __('İsim'),
            'surname' => __('Soyisim'),
            'phone' => __('Telefon Numarası'),
            'password' => __('Parola'),
            'acceptTerms' => __('Site kullanım şartları ve gizlilik kuralları'),
        ];
    }

    public function save()
    {
        $this->validate();
        if (settings()->registerStatus != StatusEnum::ACTIVE->value) {
            $this->message(__('Kayıt işlemi yapılamadı, daha sonra tekrar deneyiniz!'))->error();
            return false;
        }

        $user = CreateOrUpdateUserAction::run(data: [
            'name' => $this->name,
            'surname' => $this->surname,
            'username' => $this->email,
            'email' => $this->email,
            'phone' => $this->phone ?? ''
        ]);
        if (!$user) {
            $this->message(__('Kayıt işlemi yapılamadı, daha sonra tekrar deneyiniz!'))->error();
            return false;
        }

        $this->updatePassword($user);
        $this->updateRole($user);
        $this->sendVerifyEmail($user);

        $this->reset(['name', 'surname', 'email', 'phone', 'password', 'password_confirmation']);

        $this->afterRegister = true;
        return true;
    }

    private function updatePassword($user): void
    {
        UpdatePasswordAction::run(
            user: $user,
            password: $this->password
        );
    }

    private function updateRole($user): void
    {
        UpdateRoleAction::run(
            user: $user,
            role_id: settings()->defaultRole ?? 1
        );
    }

    private function sendVerifyEmail($user): void
    {
        Mail::to($user->email)
            ->send(new EmailVerificationMail(user: $user, locale: app()->getLocale()));
    }

    public function render()
    {
        return view('livewire.frontend.auth.register');
    }
}
