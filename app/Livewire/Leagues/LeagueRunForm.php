<?php

namespace App\Livewire\Leagues;

use App\Jobs\CalculateLeagueResult;
use App\Traits\CustomLivewireAlert;
use Livewire\Component;

class LeagueRunForm extends Component
{
    use CustomLivewireAlert;

    public string $locale;

    public string $start_at;

    public string $end_at;

    public function mount(): void
    {
        $this->requireAccess();
        $this->locale = app()->getLocale();
        $this->start_at = now()->subDays(7)->format('Y-m-d 00:00');
        $this->end_at = now()->format('Y-m-d 23:59');
    }

    public function run()
    {
        $this->requireAccess();
        $this->validate([
            'start_at' => ['required', 'date_format:Y-m-d H:i'],
            'end_at' => ['required', 'date_format:Y-m-d H:i', 'after_or_equal:start_at'],
        ]);
        if (settings()->leagueStatus == 'passive') {
            $this->message(__('Lig sistemi pasif durumda, hesaplama yapılamaz!'))->error();

            return false;
        }

        CalculateLeagueResult::dispatchSync(
            started_at: $this->start_at,
            ended_at: $this->end_at
        );

        return redirect()->route('admin.leagues.index')->with([
            'status' => 'success',
            'message' => __('Lig sonuçları hesaplaması yeniden çalıştırıldı, az sonra tamamlancaktır.'),
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
