<?php

namespace App\Livewire\Leagues;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\League;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class LeagueTable extends Component
{
    use CustomLivewireAlert;

    public function render()
    {
        LearningTable::access('leagues');

        return view('livewire.backend.leagues.server-table');
    }

    public function showDetail(int $id): void
    {
        LearningTable::access('leagues');
        League::findOrFail($id);
        $this->dispatch('open-learning-table', view: 'leagues.league-results-table', recordId: $id);
    }

    public function delete(int $id): bool
    {
        LearningTable::access('leagues');
        abort_unless(auth()->user()->can('leagues:delete'), 403);
        $record = League::findOrFail($id);
        $record->results()->delete();
        $deleted = $record->delete();
        TableCache::invalidate('league-results');
        $this->dispatch('refresh-server-table', table: 'leagues');

        return $deleted;
    }
}
