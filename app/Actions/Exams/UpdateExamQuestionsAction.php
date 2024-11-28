<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateExamQuestionsAction
{
    use AsAction;

    public function handle(Exam $exam, array $questions): Exam
    {
        /*
         * $questions: array
         * [question_id => [order => int]]
         * [5 => [order => 1], 6 => [order => 2]]
         * */

        $exam->questions()->sync($questions);

        return $exam;
    }
}
