<?php

namespace App\Console\Commands;

use App\Exceptions\LeagueCalculationInProgress;
use App\Jobs\CalculateLeagueResult;
use Illuminate\Console\Command;

class RunCalculateLeagueResult extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-calculate-league-result';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lig sonuçları hesaplamak için';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (settings()->leagueStatus == 'passive') {
            $this->comment('lig hesaplama kapalı');

            return self::SUCCESS;
        }

        $timezone = settings()->timezone ?? config('app.timezone');
        $now = now()->timezone($timezone);
        $day = (int) (settings()->leagueDay ?? 6);
        if ($now->dayOfWeek !== $day) {
            $this->comment(sprintf(
                'Seçilen gün (%d) ile çalışan gün (%d) aynı değil!',
                $now->dayOfWeek,
                $day
            ));

            return self::SUCCESS;
        }

        $hour = settings()->leagueHour ?? '00:00';
        if ($now->format('H:00') !== $hour) {
            $this->comment(sprintf(
                'Seçilen saat (%s) ile çalışan saat (%s) aynı değil!',
                $now->format('H:00'),
                $hour
            ));

            return self::SUCCESS;
        }

        $offsetDays = intval(settings()->leagueStartDays ?? 7);
        try {
            CalculateLeagueResult::dispatchSync(
                started_at: $now->copy()->subDays($offsetDays)->format('Y-m-d H:00:00'),
                ended_at: $now->format('Y-m-d H:00:00')
            );
        } catch (LeagueCalculationInProgress) {
            $this->comment('League calculation is already running; nothing changed.');

            return self::SUCCESS;
        }

        $this->comment('Lig sonuçları hesaplaması çalıştırıldı > '.now()->format('Y-m-d H:00'));

        return self::SUCCESS; // = 0
    }
}
