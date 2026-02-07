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
        $this->locale = app()->getLocale();
        $this->start_at = now()->subDays(7)->format('Y-m-d 00:00');
        $this->end_at = now()->format('Y-m-d 23:59');
    }

    public function run()
    {
        if ($this->start_at > $this->end_at) {
            $this->message(__('Başlangıç zamanı bbitiş zamanından daha sonra olamaz!'))->error();
            return false;
        }

        CalculateLeagueResult::dispatchSync(
            started_at: $this->start_at,
            ended_at: $this->end_at
        );

        return redirect()->route('admin.leagues.index')->with([
            'status' => 'success',
            'message' => __('Lig sonuçları hesaplaması yeniden çalıştırıldı, az sonra tamamlancaktır.')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.leagues.league-run-form');
    }
}
