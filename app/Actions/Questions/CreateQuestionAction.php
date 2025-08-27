<?php

namespace App\Actions\Questions;

use App\Models\Question;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateQuestionAction
{
    use AsAction;

    public function handle(array $data, array $answers)
    {
        if (!empty($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }

        $question = Question::create($data);
        if ($question) {
            $question->answers()->createMany($answers);
        }

       resetCache();

        return $question;
    }
}
