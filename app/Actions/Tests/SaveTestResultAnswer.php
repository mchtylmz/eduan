<?php

namespace App\Actions\Tests;

use App\Enums\YesNoEnum;
use App\Models\Answer;
use App\Models\TestsQuestion;
use App\Models\TestsResult;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SaveTestResultAnswer
{
    use AsAction;

    public function handle(TestsResult $result, int $sectionId, int $questionId, int $answerId): TestsResult
    {
        abort_unless(auth()->user()?->can('tests:solve'), 403);

        return DB::transaction(function () use ($result, $sectionId, $questionId, $answerId) {
            $result = TestsResult::whereKey($result->id)->where('user_id', auth()->id())
                ->lockForUpdate()->firstOrFail();

            if (YesNoEnum::YES->is($result->completed)) {
                return $result;
            }

            abort_unless($result->expires_at, 409);
            if ($result->expires_at->lessThanOrEqualTo(now())) {
                return CompleteTestResult::run($result);
            }

            $question = TestsQuestion::where('test_id', $result->test_id)
                ->where('section_id', $sectionId)->where('question_id', $questionId)->first();

            abort_unless($question, 422);
            abort_unless($answerId === 0 || Answer::whereKey($answerId)
                ->where('question_id', $questionId)->exists(), 422);

            $result->details()->updateOrCreate([
                'section_id' => $sectionId,
                'question_id' => $questionId,
            ], [
                'answer_id' => $answerId,
                'correct' => YesNoEnum::EMPTY,
                'point' => 0,
                'lesson_id' => $question->lesson_id,
                'topic_id' => $question->topic_id,
                'time' => 0,
            ]);

            return $result;
        });
    }
}
