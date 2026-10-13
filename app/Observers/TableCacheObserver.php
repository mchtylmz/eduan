<?php

namespace App\Observers;

use App\Helpers\FrontendCache;
use App\Helpers\TableCache;
use Illuminate\Database\Eloquent\Model;

class TableCacheObserver
{
    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model, true);
    }

    public function restored(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model, bool $deleted = false): void
    {
        FrontendCache::modelChanged($model, $deleted);

        $tables = match ($model->getTable()) {
            'lessons', 'topics', 'questions' => ['lessons', 'topics', 'questions', 'ai', 'exams', 'user-unsolved-topics', 'exam-questions', 'exam-result-details', 'test-result-details', 'result-stats', 'question-stats', 'options'],
            'users' => ['user-favorites', 'user-students', 'user-teachers', 'users', 'roles', 'logs', 'reviews', 'exam-results', 'exam-favorites', 'test-results', 'ai-votes', 'ai-usages', 'league-results', 'result-stats', 'question-stats', 'options'],
            'roles' => ['roles', 'users', 'options'],
            'logs' => ['logs'],
            'blogs' => ['blogs'],
            'pages' => ['pages'],
            'faqs' => ['faqs'],
            'contacts' => ['contacts'],
            'newsletters' => ['newsletter'],
            'exam_reviews' => ['reviews'],
            'exams' => ['user-favorites', 'reviews', 'exams', 'user-unsolved-topics', 'exam-questions', 'exam-favorites', 'exam-results', 'result-stats', 'options'],
            'answer_a_i_s', 'answer_a_i_votes', 'user_ai_usages' => ['ai', 'ai-votes', 'ai-usages', 'options'],
            'latex_images' => ['ai-latex-images'],
            'exam_results' => ['user-unsolved-topics', 'exam-results', 'exam-result-details', 'test-result-details', 'result-stats', 'question-stats', 'options'],
            'exam_result_details', 'answers' => ['user-unsolved-topics', 'exam-result-details', 'test-result-details', 'result-stats', 'question-stats', 'options'],
            'tests', 'tests_sections', 'tests_questions' => ['tests', 'test-results', 'result-stats', 'options'],
            'tests_results', 'tests_result_details' => ['user-unsolved-topics', 'test-results', 'test-result-details', 'question-stats', 'result-stats', 'options'],
            'leagues', 'league_results' => ['leagues', 'league-results', 'options'],
            'seasons' => ['user-unsolved-topics', 'exam-results', 'test-results', 'options'],
            default => ['user-favorites', 'user-unsolved-topics', 'user-students', 'user-teachers', 'tests', 'test-results', 'test-result-details', 'leagues', 'league-results', 'ai-votes', 'ai-usages', 'result-stats', 'question-stats', 'lessons', 'topics', 'users', 'roles', 'logs', 'blogs', 'pages', 'faqs', 'contacts', 'newsletter', 'languages', 'reviews', 'questions', 'ai', 'exams', 'exam-results', 'exam-questions', 'exam-favorites', 'exam-result-details', 'options'],
        };
        TableCache::invalidate(...$tables);
    }
}
