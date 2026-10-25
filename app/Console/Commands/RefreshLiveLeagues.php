<?php

namespace App\Console\Commands;

use App\Actions\Leagues\CalculateLeague;
use App\Exceptions\LeagueCalculationInProgress;
use App\Models\League;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshLiveLeagues extends Command
{
    protected $signature = 'leagues:refresh-live';

    protected $description = 'Refresh started live leagues and finalize expired periods.';

    public function handle(): int
    {
        if (settings()->leagueStatus === 'passive') {
            $this->comment('League calculation is disabled; nothing changed.');

            return self::SUCCESS;
        }

        $updated = $closed = $skipped = $failed = 0;
        League::withoutGlobalScope('default_order_by')->select('id')
            ->where('is_live', true)->whereNull('closed_at')
            ->whereNotNull('start_at')->whereNotNull('end_at')->where('start_at', '<=', now())
            ->chunkById(50, function ($leagues) use (&$updated, &$closed, &$skipped, &$failed) {
                foreach ($leagues as $league) {
                    try {
                        // Recheck eligibility under the same DB/period lock used by weekly/manual calls.
                        $result = CalculateLeague::run('', '', leagueId: $league->id, scheduled: true);
                        if ($result === null) {
                            $skipped++;

                            continue;
                        }
                        $updated++;
                        if ($result->closed_at !== null) {
                            $closed++;
                        }
                    } catch (LeagueCalculationInProgress|ModelNotFoundException) {
                        $skipped++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::error('Live league calculation failed.', ['league_id' => $league->id, 'exception' => $e::class]);
                        $this->warn('Live league '.$league->id.' could not be calculated.');
                    }
                }
            });
        $this->info("Updated: $updated; closed: $closed; skipped: $skipped; failed: $failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
