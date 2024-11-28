<?php

namespace App\Livewire\Home;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class UserTable extends Component
{
    public bool $showNewRegister = true;
    public bool $showLastLogins = false;

    public function mount($register = true, $lastLogins = false)
    {
        $this->showNewRegister = $register;
        $this->showLastLogins = $lastLogins;
    }

    #[Computed]
    public function users()
    {
        if ($this->showNewRegister) {
            return cache()->remember(
                'home_user-table_register',
                60 * 60 * 3,
                fn() => User::orderByDesc('id')->limit(6)->get()
            );
        }

        if ($this->showLastLogins) {
            return cache()->remember(
                'home_user-table_login',
                60 * 60 * 3,
                fn() => User::whereNotNull('last_login_at')->orderByDesc('last_login_at')->limit(6)->get()
            );
        }

        return [];
    }

    public function render()
    {
        return view('livewire.backend.home.user-table');
    }
}
