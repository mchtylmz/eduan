<?php

namespace App\Livewire\Leagues;

use App\Actions\Leagues\RecalculateClosedLeague;
use App\Exceptions\LeagueCalculationInProgress;
use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\League;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;
use Throwable;

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

    public function recalculate(int $id): void
    {
        LearningTable::access('leagues');
        // Recheck before the error handler so denied/stale calls remain HTTP 403/409.
        abort_unless(auth()->user()->can('leagues:run'), 403);
        $record = League::findOrFail($id);
        abort_unless($record->is_live && $record->closed_at !== null, 409);
        if (settings()->leagueStatus === 'passive') {
            $this->message(__('Lig sistemi pasif durumda, hesaplama yapılamaz!'))->error();

            return;
        }
        try {
            RecalculateClosedLeague::run($id);
        } catch (LeagueCalculationInProgress) {
            $this->message(__('leagues.busy'))->error();

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->message(__('leagues.failed'))->error();

            return;
        }
        $this->message(__('leagues.saved'))->success();
        $this->dispatch('refresh-server-table', table: 'leagues');
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
