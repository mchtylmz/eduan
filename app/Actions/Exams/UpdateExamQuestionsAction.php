<?php

namespace App\Actions\Exams;

use App\Helpers\FrontendCache;
use App\Helpers\TableCache;
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

        $formattedQuestions = [];
        foreach ($questions as $questionId => $questionData) {
            $formattedQuestions[$questionId] = [
                'order' => $questionData['order'] ?? 1,
                'lesson_id' => $questionData['lesson_id'],
                'topic_id' => $questionData['topic_id'],
            ];
        }

        $exam->questions()->sync($formattedQuestions);
        TableCache::invalidate('exams', 'exam-questions');
        FrontendCache::invalidate('lessons', 'topics', 'tests', 'navigation');

        return $exam;
    }
}
