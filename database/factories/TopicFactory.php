<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Arr::random(Lesson::all()->pluck('id')->toArray()),
            'code' => fake()->unique()->slug(1),
            'sort' => fake()->numberBetween(1,1000),
            'hits' => 0,
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
