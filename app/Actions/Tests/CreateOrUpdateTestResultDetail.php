<?php

namespace App\Actions\Tests;

use App\Enums\YesNoEnum;
use App\Models\Answer;
use App\Models\Question;
use App\Models\TestsResult;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateOrUpdateTestResultDetail
{
    use AsAction;

    public function handle(array $results, TestsResult $testResult)
    {
        /*
         results = [
            section_index => [
                parent_id => [
                    question_id => answer_id
                ],
                parent_id => [
                    question_id => answer_id
                ]
            ],
            section_index => [
                parent_id => [
                    question_id => answer_id
                ],
                parent_id => [
                    question_id => answer_id
                ]
            ],
        ]
        ////
        [
            'section_index' => section_index,
            'parent_id' => parent_id,
            'question_id' => question_id,
            'answer_id' => answer_id,
        ]
        */
        $data = [];
        collect($results)->each(function ($result) use (&$data, $testResult) {
            collect($result)->each(function ($resultItem, $parent_id) use (&$data, $testResult) {
                $questionId = array_key_first($resultItem) ?? 0;
                $answerId = array_values($resultItem)[0] ?? 0;

                $data[] = [
                    'tests_result_id' => $testResult->id,
                    'section_id' => $parent_id,
                    'question_id' => $questionId,
                    'answer_id' => $answerId,
                    'correct' => $answerId == 0 ? YesNoEnum::EMPTY: YesNoEnum::NO,
                    'point' => 0,
                    'lesson_id' => 0,
                    'topic_id' => 0,
                    'time' => 0
                ];
            });

        });

        if (!$data) {
            return $testResult;
        }

        $testResult->details()->upsert(
            $data,
            [
                'tests_result_id',
                'section_id',
                'question_id'
            ],
            [
                'tests_result_id',
                'section_id',
                'question_id',
                'answer_id',
                'correct',
                'point',
                'lesson_id',
                'topic_id',
                'time'
            ]
        );

        DB::table('tests_result_details')
            ->where('tests_result_id', $testResult->id)
            ->where('correct', YesNoEnum::NO)
            ->update([
                'correct' => DB::raw('(SELECT correct FROM answers WHERE id = answer_id)')
            ]);

        DB::table('tests_result_details')
            ->where('tests_result_id', $testResult->id)
            ->where('point', 0)
            ->where('correct', '!=', YesNoEnum::EMPTY)
            ->update([
                'point' => DB::raw(sprintf(
                    'IF(correct = 1, %d, %d)',
                    $testResult->test->correct_point ?: 3,
                    $testResult->test->incorrect_point ?: -1
                ))
            ]);

        DB::table('tests_result_details')
            ->where('tests_result_id', $testResult->id)
            ->update([
                'lesson_id' => DB::raw('(SELECT lesson_id FROM questions WHERE id = question_id)'),
                'topic_id' => DB::raw('(SELECT topic_id FROM questions WHERE id = question_id)')
            ]);

        $testResult->update([
            'correct_count' => DB::raw('(SELECT COUNT(correct) FROM tests_result_details WHERE tests_result_id = tests_results.id AND correct = 1 AND answer_id != 0)'),
            'incorrect_count' => DB::raw('(SELECT COUNT(correct) FROM tests_result_details WHERE tests_result_id = tests_results.id AND correct = 0 AND answer_id != 0)'),
            'point' => DB::raw(
                '(SELECT SUM(point) FROM tests_result_details WHERE tests_result_id = tests_results.id)'
            ),
            'completed' => YesNoEnum::YES,
            'completed_at' => now(),
            'duration' => (int) (now()->timestamp - strtotime($testResult->created_at))
        ]);

        return TestsResult::find($testResult->id);
    }
}
