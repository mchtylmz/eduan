<?php

namespace App\Actions\Questions;

use App\Models\Question;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateQuestionAction
{
    use AsAction;

    public function handle(array $data, array $answers)
    {
        $question = Question::create($data);
        if ($question) {
            $question->answers()->createMany($answers);
        }

        flush();

        return $question;
    }
}
