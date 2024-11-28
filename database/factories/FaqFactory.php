<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'locale' => Arr::random(Language::all()->pluck('code')->toArray()),
            'title' => fake()->text(24),
            'content' => implode('<br>', fake()->paragraphs(5)),
            'sort' => fake()->numberBetween(1, 100),
        ];
    }
}
