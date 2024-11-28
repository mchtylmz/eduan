<?php

namespace App\Livewire\Frontend\Account;

use App\Actions\Users\CreateOrUpdateUserAction;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class ProfileForm extends Component
{
    use CustomLivewireAlert;

    public User $user;

    public string $name;
    public string $surname;
    public string $email;
    public string $phone;

    public function mount(): void
    {
        $this->user = User::find(auth()->id());
        $this->name = $this->user->name;
        $this->surname = $this->user->surname;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'surname' => 'required|string|min:2',
            'email' => [
                'required', 'string', 'min:3', 'email:rfc',
                Rule::unique('users', 'username')->ignore($this->user->id ?? ''),
            ],
            'phone' => 'nullable|string'
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'email' => __('E-posta Adresiniz'),
            'name' => __('İsim'),
            'surname' => __('Soyisim'),
            'phone' => __('Telefon Numarası')
        ];
    }

    public function save()
    {
        $this->validate();

        CreateOrUpdateUserAction::run(
            data: [
                'email' => $this->email,
                'name' => $this->name,
                'surname' => $this->surname,
                'phone' => $this->phone
            ],
            user: $this->user
        );

        $this->message(__('Bilgileriniz başarıyla güncellendi'))->success();
    }

    public function render()
    {
        return view('livewire.frontend.account.profile-form');
    }
}
