<?php

namespace App\Jobs;

use App\Models\Season;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateSeasonIdForResults implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $seasons = Season::all();
        foreach ($seasons as $season) {
            \App\Models\TestsResult::whereBetween('updated_at',[$season->start_date, $season->end_date])
                ->orWhereBetween('completed_at',[$season->start_date, $season->end_date])
                ->update(['season_id' => $season->id]);

            \App\Models\ExamResult::whereBetween('updated_at',[$season->start_date, $season->end_date])
                ->update(['season_id' => $season->id]);
        }
    }
}
