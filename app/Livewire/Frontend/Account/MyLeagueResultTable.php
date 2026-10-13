<?php

namespace App\Livewire\Frontend\Account;

use App\Models\LeagueResult;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class MyLeagueResultTable extends Component
{
    use WithPagination;

    #[Locked]
    public int $userId;

    public function mount(int $userId): void
    {
        abort_unless(auth()->check() && $userId === auth()->id(), 403);
        $this->userId = auth()->id();
    }

    #[Computed]
    public function results()
    {
        return LeagueResult::with('league')->where('user_id', auth()->id())->latest()->orderByDesc('id')->paginate(15);
    }

    public function render()
    {
        return view('livewire.frontend.account.my-league-result-table');
    }
}
