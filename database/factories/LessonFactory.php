<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(1),
            'sort' => fake()->numberBetween(1,1000),
            'hits' => 0,
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
