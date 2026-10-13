<?php

namespace App\Jobs;

use App\Actions\Tests\CompleteTestResult;
use App\Enums\YesNoEnum;
use App\Models\TestsResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CompleteExpiredTestResult implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $resultId) {}

    public function handle(): void
    {
        $result = TestsResult::find($this->resultId);
        if (! $result || YesNoEnum::YES->is($result->completed) || ! $result->expires_at) {
            return;
        }

        if ($result->expires_at->isFuture()) {
            $this->release(max(1, $result->expires_at->timestamp - now()->timestamp));

            return;
        }

        CompleteTestResult::run($result);
    }
}
