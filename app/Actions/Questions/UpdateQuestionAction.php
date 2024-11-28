<?php

namespace App\Actions\Questions;

use App\Models\Question;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateQuestionAction
{
    use AsAction;

    public function handle(Question $question, array $data, array $answers)
    {
        $question->update($data);

        $this->answerCreateOrUpdate($question, $answers);

        return $question;
    }

    public function answerCreateOrUpdate(Question $question, array $answers): void
    {
        $activeAnswerIds = collect($answers)
            ->whereNotNull('id')
            ->pluck('id')
            ->toArray();

        $question->answers()
            ->whereNotIn('id', $activeAnswerIds)
            ->delete();

        foreach ($answers as $answer) {
            $question->answers()->updateOrCreate(
                [
                    'id' => $answer['id'] ?? 0
                ],
                [
                    'title' => $answer['title'],
                    'correct' => $answer['correct']
                ]
            );
        }
    }
}
