<?php

namespace App\Console\Commands;

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
        $day = (int) (settings()->leagueDay ?? 6);
        if (now()->dayOfWeek !== $day) {
            $this->error(sprintf(
                'Seçilen gün (%d) ile çalışan gün (%d) aynı değil!',
                now()->dayOfWeek,
                $day
            ));
            return self::FAILURE;
        }

        $hour = settings()->leagueHour ?? '00:00';
        if (now()->format('H:00') !== $hour) {
            $this->error(sprintf(
                'Seçilen saat (%s) ile çalışan saat (%s) aynı değil!',
                now()->format('H:00'),
                $hour
            ));
            return self::FAILURE;
        }

        $offsetDays = intval(settings()->leagueStartDays ?? 7);
        $timezone = settings()->timezone ?? config('app.timezone');

        CalculateLeagueResult::dispatchSync(
            started_at: now()->timezone($timezone)->subDays($offsetDays)->format('Y-m-d H:00:00'),
            ended_at: now()->timezone($timezone)->format('Y-m-d H:00:00')
        );

        $this->comment('Lig sonuçları hesaplaması çalıştırıldı > ' . now()->format('Y-m-d H:00'));
        return self::SUCCESS; // = 0
    }
}
