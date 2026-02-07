<?php

namespace App\Livewire\Frontend\Leagues;

use App\Models\League;
use App\Models\LeagueResult;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class LeagueResultTable extends Component
{
    public League $league;

    public string $name;

    public int $userCount = 0;

    public int $ranking = 1;

    public function mount(League $league, string $name, int $ranking = 1, int $userCount = null): void
    {
        $this->league = $league;
        $this->name = $name;
        $this->ranking = $ranking;
        $this->userCount = intval($userCount ?? (settings()->leagueUserCount ?? 10));
    }

    #[Computed]
    public function results()
    {
        return cache()->remember(
            sprintf('league_%d_%d', $this->league->id, $this->ranking),
            now()->addHours(12),
            function () {
                return $this->league->results()
                    ->with('user')
                    ->when(
                        $this->ranking == 1,
                        function ($query) {
                            return $query->whereBetween('position', [1, $this->userCount])
                                ->where('rank_position', '>', 0)
                                ->where('rank_position', '<=', $this->userCount);
                        }
                    )
                    ->when(
                        $this->ranking == 2,
                        function ($query) {
                            return $query->whereBetween('position', [1, $this->userCount])
                                ->where('rank_position', '>', $this->userCount)
                                ->where('rank_position', '<=', $this->userCount * 2);
                        }
                    )
                    ->when(
                        $this->ranking == 3,
                        function ($query) {
                            return $query->whereBetween('position', [1, $this->userCount])
                                ->where('rank_position', '>', $this->userCount * 2)
                                ->where('rank_position', '<=', $this->userCount * 3);
                        }
                    )
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
