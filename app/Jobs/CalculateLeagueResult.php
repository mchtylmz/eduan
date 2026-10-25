<?php

namespace App\Jobs;

use App\Actions\Leagues\CalculateLeague;
use App\Helpers\LeaguePeriod;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateLeagueResult implements ShouldQueue
{
    use Queueable;

    public string $started_at;

    public string $ended_at;

    public function __construct(string $started_at, string $ended_at)
    {
        $period = LeaguePeriod::fromStrings($started_at, $ended_at);
        $this->started_at = $period->start->format('Y-m-d H:i:s');
        $this->ended_at = $period->end->format('Y-m-d H:i:s');
    }

    public function handle(): void
    {
        if (settings()->leagueStatus === 'passive') {
            $this->fail('lig hesaplama kapalı');

            return;
        }
        // Manual and old weekly callers retain the same two-argument contract.
        $league = CalculateLeague::run($this->started_at, $this->ended_at);
        if ($league === null) {
            $this->fail('Lig hesaplama aralığında data yok! - '.$this->started_at.'-'.$this->ended_at);
        }
    }
}
