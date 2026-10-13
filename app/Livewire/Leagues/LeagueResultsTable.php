<?php

namespace App\Livewire\Leagues;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\League;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LeagueResultsTable extends Component
{
    #[Locked]
    public int $leagueId;

    public function mount(int $leagueId): void
    {
        LearningTable::access('leagues');
        $this->leagueId = $leagueId;
    }

    public function render()
    {
        LearningTable::access('leagues');
        League::findOrFail($this->leagueId);
        $students = LearningTable::studentIds();
        $scope = hash('sha256', json_encode($students));

        return view('livewire.backend.leagues.results-server-table', ['users' => TableCache::options('league-users-'.$scope, fn () => User::when($students !== null, fn ($q) => $q->whereIn('id', $students))
            ->get(['id', 'name', 'surname', 'email'])->mapWithKeys(fn ($u) => [$u->id => sprintf('(%s) %s', $u->email, $u->display_name)])->all())]);
    }
}
