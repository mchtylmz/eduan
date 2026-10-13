<?php

namespace App\Actions\Tests;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Jobs\CompleteExpiredTestResult;
use App\Models\Test;
use App\Models\TestsResult;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class StartTestResult
{
    use AsAction;

    public function handle(Test $test): TestsResult
    {
        abort_unless(auth()->user()?->can('tests:solve'), 403);

        return DB::transaction(function () use ($test) {
            $test = Test::whereKey($test->id)->lockForUpdate()->firstOrFail();
            abort_unless(StatusEnum::ACTIVE->is($test->status), 403);

            $result = $test->results()->where('user_id', auth()->id())
                ->where('completed', YesNoEnum::NO)->latest('id')->first();

            if ($result) {
                abort_unless($result->expires_at, 409);

                return $result->expires_at->lessThanOrEqualTo(now())
                    ? CompleteTestResult::run($result)
                    : $result;
            }

            $result = $test->results()->create([
                'user_id' => auth()->id(),
                'question_count' => $test->questions()->count(),
                'passing_score' => $test->passing_score,
                'completed' => YesNoEnum::NO,
                'expires_at' => now()->addSeconds($test->duration),
                'season_id' => activeSeason()->id ?? 1,
            ]);

            CompleteExpiredTestResult::dispatch($result->id)
                ->delay($result->expires_at)->afterCommit();

            return $result;
        });
    }
}
