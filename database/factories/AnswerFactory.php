<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Arr::random(Question::all()->pluck('id')->toArray()),
            'title' => Arr::random(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']),
            'correct' => fake()->boolean ? YesNoEnum::YES : YesNoEnum::NO,
        ];
    }
}
