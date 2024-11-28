<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Exam::factory(100)->create();

        $lessons = Lesson::all()->pluck('id')->toArray();
        $topics = Topic::all()->pluck('id')->toArray();
        $questions = Question::all()->pluck('id')->toArray();

        foreach (Exam::all() as $exam) {
            $examQuestions = [];

            for ($i = 1; $i <= rand(2, 10); $i++) {
                $examQuestions[Arr::random($questions)] = [
                    'order' => $i,
                    'lesson_id' => Arr::random($lessons),
                    'topic_id' => Arr::random($topics)
                ];
            }

            if (!$examQuestions) {
                $examQuestions = [
                    [
                        'order' => 1,
                        'lesson_id' => Arr::random($lessons),
                        'topic_id' => Arr::random($topics)
                    ],
                    [
                        'order' => 2,
                        'lesson_id' => Arr::random($lessons),
                        'topic_id' => Arr::random($topics)
                    ],
                    [
                        'order' => 3,
                        'lesson_id' => Arr::random($lessons),
                        'topic_id' => Arr::random($topics)
                    ]
                ];
            }

            $exam->questions()->sync($examQuestions);
        }
    }
}
