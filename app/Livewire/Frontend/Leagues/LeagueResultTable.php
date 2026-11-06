<?php

namespace App\Livewire\Frontend\Leagues;

use App\Helpers\LeagueCache;
use App\Helpers\LeagueRules;
use App\Models\League;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LeagueResultTable extends Component
{
    public League $league;

    public string $name;

    public int $userCount = 0;

    public int $ranking = 1;

    public function mount(League $league, string $name, int $ranking = 1, ?int $userCount = null): void
    {
        $this->league = $league;
        $this->name = $name;
        $this->ranking = $ranking;
        $this->userCount = $league->is_live || $league->calculation_settings !== null
            ? LeagueRules::fromSnapshot($league->label, $league->calculation_settings ?? [])->userCount
            : intval($userCount ?? 0);
    }

    #[Computed]
    public function results()
    {
        return cache()->remember(
            LeagueCache::resultsKey($this->league->id, $this->ranking, $this->userCount),
            now()->addHours(12),
            function () {
                return $this->league->results()
                    ->with('user')
                    ->where('league_number', $this->ranking)
                    ->where('position', '>', 0)
                    ->where('rank_position', '>', 0)
                    ->orderBy('rank_position')
                    ->orderBy('position')
                    ->get();
            }
        );
    }

    public function render()
    {
        return view('livewire.frontend.leagues.league-result-table');
    }
}
