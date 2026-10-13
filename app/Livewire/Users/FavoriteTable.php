<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FavoriteTable extends Component
{
    #[Locked]
    public int $userId;

    public function mount(int $userId): void
    {
        AuthorizeStudentAccess::run(User::findOrFail($userId));
        $this->userId = $userId;
    }

    public function render()
    {
        $user = User::findOrFail($this->userId);
        AuthorizeStudentAccess::run($user);

        return view('livewire.backend.users.favorites-server-table', ['contextUserId' => $user->id]);
    }
}
