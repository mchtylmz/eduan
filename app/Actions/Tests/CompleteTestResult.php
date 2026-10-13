<?php

namespace App\Actions\Tests;

use App\Enums\YesNoEnum;
use App\Models\TestsQuestion;
use App\Models\TestsResult;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CompleteTestResult
{
    use AsAction;

    public function handle(TestsResult $result): TestsResult
    {
        return DB::transaction(function () use ($result) {
            $result = TestsResult::whereKey($result->id)->lockForUpdate()->firstOrFail();
            if (YesNoEnum::YES->is($result->completed)) {
                return $result;
            }

            $answers = $result->details()->get()->keyBy(fn ($detail) => $detail->section_id.':'.$detail->question_id);
            $questions = TestsQuestion::where('test_id', $result->test_id)->get();
            $results = [];
            foreach ($questions as $question) {
                $answer = $answers->get($question->section_id.':'.$question->question_id);
                $results[] = [$question->section_id => [$question->question_id => $answer?->answer_id ?? 0]];
            }

            $completedAt = $result->expires_at && $result->expires_at->lessThanOrEqualTo(now())
                ? $result->expires_at : now();

            if ($results) {
                $result = CreateOrUpdateTestResultDetail::run($results, $result);
            }

            $result->update([
                'completed' => YesNoEnum::YES,
                'completed_at' => $completedAt,
                'duration' => max(0, $completedAt->timestamp - $result->created_at->timestamp),
            ]);

            return $result->refresh();
        });
    }
}
