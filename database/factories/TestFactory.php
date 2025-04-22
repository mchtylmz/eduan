<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Test>
 */
class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = sprintf('SINAV %s', fake()->numberBetween(1, 1000));

        return [
            'locale' => Arr::random(Language::all()->pluck('code')->toArray()),
            'code' => Str::slug($name),
            'name' => $name,
            'content' => implode('<br>', fake()->paragraphs(10)),
            'duration' => fake()->numberBetween(60, 3600),
            'status' => StatusEnum::ACTIVE,
            'created_by' => 1
        ];
    }
}
