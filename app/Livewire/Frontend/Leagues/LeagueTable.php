<?php

namespace App\Livewire\Frontend\Leagues;

use App\Models\League;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: false)]
class LeagueTable extends Component
{
    public League $selectedLeague;

    #[Computed(cache: true)]
    public function leagues()
    {
        return cache()->remember('leagues_all', now()->addHours(24), function () {
            return League::orderBy('id', 'DESC')->get();
        });
    }

    public function render()
    {
        return view('livewire.frontend.leagues.league-table');
    }
}
