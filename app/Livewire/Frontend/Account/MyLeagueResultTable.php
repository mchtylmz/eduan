<?php

namespace App\Livewire\Frontend\Account;

use App\Models\LeagueResult;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyLeagueResultTable extends Component
{
    public int $userId;

    public array $leagueNames = [];

    public function mount(int $userId): void
    {
        $this->userId = $userId;

        $this->leagueNames = [
            1 => __('1. Lig'),
            2 => __('2. Lig'),
            3 => __('3. Lig'),
            4 => __('4. Lig'),
            5 => __('5. Lig'),
            6 => __('6. Lig'),
            7 => __('7. Lig'),
        ];
    }

    #[Computed]
    public function results()
    {
        return LeagueResult::with('league')->where('user_id', $this->userId)->latest()->get();
    }

    public function render()
    {
        return view('livewire.frontend.account.my-league-result-table');
    }
}
