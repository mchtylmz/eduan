<?php

namespace Database\Seeders;

use App\Enums\ReviewVisibilityEnum;
use App\Enums\YesNoEnum;
use App\Models\Exam;
use App\Models\ExamReview;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ExamReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Exam::all() as $exam) {
            for ($i = 0; $i <= rand(1, 15); $i++) {
                $reviews = ExamReview::all()->pluck('id')->toArray();

                $exam->reviews()->create([
                    'user_id' => Arr::random(User::all()->pluck('id')->toArray()),
                    'comment' => fake()->text(150),
                    'visibility' => ReviewVisibilityEnum::PUBLIC,
                    'reply_id' => Arr::random([0, ...$reviews]),
                ]);
            }
        }
    }
}
