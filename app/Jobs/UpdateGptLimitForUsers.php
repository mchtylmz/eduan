<?php

namespace App\Jobs;

use App\Enums\StatusEnum;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateGptLimitForUsers implements ShouldQueue
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
        $limit = settings()->gptLimitCount ?? 5;

        // User::where('status', StatusEnum::ACTIVE)->update(['gpt_limit' => $limit]);
    }
}
