<?php

namespace App\Livewire\Users;

use App\Actions\Users\CreateOrUpdateUserAction;
use App\Actions\Users\UpdatePasswordAction;
use App\Actions\Users\UpdateRoleAction;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Role;
use App\Models\User;
use App\Traits\CustomLivewireAlert;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class UserForm extends Component
{
    use CustomLivewireAlert;

    public ?User $user = null;

    public ?int $role_id;
    public string $username;
    public string $name;
    public string $surname;
    public ?string $password = null;
    public StatusEnum $status = StatusEnum::ACTIVE;
    public YesNoEnum $email_verified = YesNoEnum::NO;

    public string $permission = 'users:add';

    public function mount(?User $user = null): void
    {
        $this->user = $user;

        if (!empty($this->user) && $this->user->exists) {
            $this->initializeForExistingUser();
        } else {
            $this->initializeForNewUser();
        }
    }

    private function initializeForExistingUser(): void
    {
        $this->role_id = $this->user->roles->first()?->id ?? null;
        $this->username = $this->user->username;
        $this->name = $this->user->name;
        $this->surname = $this->user->surname;
        $this->status = $this->user->status;
        $this->email_verified = $this->user->email_verified ?? YesNoEnum::NO;
        $this->permission = 'users:update';
    }

    private function initializeForNewUser(): void
    {
        $this->password = Str::random(6);
    }

    #[Computed]
    public function roles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::all();
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'email:rfc,dns',
                Rule::unique('users', 'username')->ignore($this->user?->id),
            ],
            'password' => [
                Rule::requiredIf(fn () => empty($this->user) || !$this->user->exists)
            ],
            'name' => 'required|string',
            'surname' => 'required|string',
            'role_id' => 'required|integer|exists:roles,id',
            'status' => ['required', new Enum(StatusEnum::class)],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'username' => __('Kullanıcı Adı'),
            'password' => __('Parola'),
            'name' => __('İsim'),
            'surname' => __('Soyisim'),
            'role_id' => __('Kullanıcı Yetkisi'),
            'status' => __('Durum'),
        ];
    }

    public function save()
    {
        $this->validate();

        if ($this->isUnauthorized()) {
            return false;
        }

        $user = $this->createOrUpdateUser();
        if (!$user) {
            $this->message(__('Kullanıcı adı tanımlaması yapılamadı!'))->error();
            return false;
        }

        $this->updateUserPassword($user);
        $this->updateUserRole($user);

        return redirect()->route('admin.users.index')->with([
            'status' => 'success',
            'message' => __('Kullanıcı bilgisi kayıt edildi!')
        ]);
    }

    private function isUnauthorized(): bool
    {
        if (auth()->user()->cannot($this->permission)) {
            $this->message(__('Kullanıcı bilgisi kayıt edilemez, yetkiniz bulunmuyor!'))->error();
            return true;
        }
        return false;
    }

    private function createOrUpdateUser(): ?User
    {
        return CreateOrUpdateUserAction::run(
            data: [
                'username' => $this->username,
                'email' => $this->username,
                'name' => $this->name,
                'surname' => $this->surname,
                'status' => $this->status,
                'email_verified' => $this->email_verified,
            ],
            user: !empty($this->user) && $this->user->exists ? $this->user : null
        );
    }

    private function updateUserPassword(User $user): void
    {
        if (!empty($this->password)) {
            UpdatePasswordAction::run(user: $user, password: $this->password);
        }
    }

    private function updateUserRole(User $user): void
    {
        UpdateRoleAction::run(user: $user, role_id: $this->role_id);
    }

    public function render()
    {
        return view('livewire.backend.users.user-form');
    }
}
