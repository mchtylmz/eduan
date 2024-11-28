<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
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
            'slug' => fake()->unique()->slug,
            'image' => 'uploads/lorem-ipsum.png',
            'title' => fake()->text(60),
            'brief' => fake()->text(60),
            'content' => collect(fake()->paragraphs(rand(7, 20)))->implode('<br><br>'),
            'keywords' => collect(fake()->words(5))->implode(','),
            'status' => StatusEnum::ACTIVE,
            'published_at' => now()->subDays(fake()->numberBetween(0, 30))
        ];
    }
}
