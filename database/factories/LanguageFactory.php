<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Language>
 */
class LanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->languageCode(),
            'name' => fake()->languageCode(),
            'status' => StatusEnum::ACTIVE,
        ];
    }
}