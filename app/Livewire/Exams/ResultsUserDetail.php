<?php

namespace App\Livewire\Exams;

use App\Models\User;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class ResultsUserDetail extends Component
{
    public User $user;

    public function mount(int $userId)
    {
        $this->user = User::find($userId);
    }

    public function render()
    {
        return view('livewire.backend.exams.results-user-detail');
    }
}
