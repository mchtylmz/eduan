<?php

namespace App\Livewire\Frontend\Auth;

use App\Actions\Users\GetUserFromPasswordTokenAction;
use App\Actions\Users\UpdatePasswordAction;
use App\Mail\CreatePasswordMail;
use App\Mail\RecoverPasswordMail;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Lazy(isolate: true)]
class CreatePassword extends Component
{
    use CustomLivewireAlert;

    #[Locked]
    public User $user;

    #[Locked]
    public string $token = '';

    public function mount(User $user, string $token): void
    {
        $verifiedUser = GetUserFromPasswordTokenAction::run($token);
        abort_unless($verifiedUser && $verifiedUser->id === $user->id, 404);
        $this->user = $verifiedUser;
        $this->token = $token;
    }

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

        DB::transaction(function () {
            $token = DB::table('password_reset_tokens')
                ->whereIn('token', [$this->token, hash('sha256', $this->token)])
                ->lockForUpdate()->first();
            $user = $token ? GetUserFromPasswordTokenAction::run($this->token) : false;
            abort_unless($user && $user->id === $this->user->id, 404);
            UpdatePasswordAction::run(user: $user, password: $this->password);
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        });
        $this->sendCreatePasswordMail();

        return redirect()->route('login')->with([
           'status' => 'success',
           'message' => __('Yeni parolanız başarıyla güncellendi')
        ]);
    }

    private function sendCreatePasswordMail(): void
    {
        Mail::to($this->user->email)
            ->send(new CreatePasswordMail(user: $this->user, locale: app()->getLocale()));
    }

    public function render()
    {
        return view('livewire.frontend.auth.create-password');
    }
}
