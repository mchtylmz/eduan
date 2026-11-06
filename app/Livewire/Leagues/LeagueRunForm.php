<?php

namespace App\Livewire\Leagues;

use App\Actions\Leagues\CalculateLeague;
use App\Exceptions\LeagueCalculationInProgress;
use App\Helpers\LeaguePeriod;
use App\Traits\CustomLivewireAlert;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class LeagueRunForm extends Component
{
    use CustomLivewireAlert;

    public string $locale;

    public string $start_at;

    public string $end_at;

    public string $is_live = '';

    public function mount(): void
    {
        $this->requireAccess();
        $this->locale = app()->getLocale();
        $period = LeaguePeriod::currentWeek(CarbonImmutable::now());
        $this->start_at = $period->start->format('Y-m-d H:i');
        $this->end_at = $period->end->format('Y-m-d H:i');
    }

    public function run()
    {
        $this->requireAccess();
        $this->validate([
            'start_at' => ['required', 'date_format:Y-m-d H:i'],
            'end_at' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:start_at'],
            'is_live' => ['required', 'boolean'],
        ]);
        if (settings()->leagueStatus == 'passive') {
            $this->message(__('Lig sistemi pasif durumda, hesaplama yapılamaz!'))->error();

            return false;
        }

        try {
            $league = CalculateLeague::run($this->start_at, $this->end_at, live: (bool) $this->is_live);
        } catch (LeagueCalculationInProgress) {
            $this->message(__('leagues.busy'))->error();

            return false;
        } catch (InvalidArgumentException) {
            $this->message(__('leagues.invalid_configuration'))->error();

            return false;
        } catch (Throwable $exception) {
            report($exception);
            $this->message(__('leagues.failed'))->error();

            return false;
        }
        if ($league === null) {
            $this->message(__('leagues.no_data'))->error();

            return false;
        }

        return redirect()->route('admin.leagues.index')->with([
            'status' => 'success',
            'message' => __('leagues.saved'),
        ]);
    }

    public function render()
    {
        $this->requireAccess();

        return view('livewire.backend.leagues.league-run-form');
    }

    private function requireAccess(): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('leagues:view') && $actor->can('leagues:run'), 403);
    }
}
