<?php

namespace App\Livewire\Users;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TopicsNotResultsTable extends Component
{
    #[Locked]
    public User $user;

    public function mount(User $user): void
    {
        AuthorizeStudentAccess::run($user);
        $this->user = $user;
    }

    public function render()
    {
        $user = $this->user;
        AuthorizeStudentAccess::run($user);

        return view('livewire.backend.users.unsolved-topics-server-table', ['contextUserId' => $user->id]);
    }
}
