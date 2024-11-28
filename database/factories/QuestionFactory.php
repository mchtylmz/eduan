<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lesson_id = Arr::random(Lesson::all()->pluck('id')->toArray());
        $topic_id = Arr::random(Topic::all()->pluck('id')->toArray());

        return [
            'lesson_id' => $lesson_id,
            'topic_id' => $topic_id ?: 1,
            'locale' => Arr::random(Language::all()->pluck('code')->toArray()),
            'code' => Str::lower(fake()->slug(1)),
            'title' => fake()->text(60),
            'attachment' => 'uploads/sample_soru'.fake()->numberBetween(1, 8).'.png',
            'solution' => 'uploads/sample_cozum'.fake()->numberBetween(1, 7).'.png',
            'sort' => fake()->numberBetween(1, 1000),
            'time' => fake()->numberBetween(30, 600),
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
