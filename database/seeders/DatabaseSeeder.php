<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        cache()->flush();

        $this->call([
            LanguageSeeder::class,
            SettingSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            LessonSeeder::class,
            TopicSeeder::class,
            QuestionSeeder::class,
            AnswerSeeder::class,
            ExamSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
            BlogSeeder::class,
            ExamReviewSeeder::class
        ]);

        DB::statement("UPDATE questions SET status = 'passive' WHERE id NOT IN(SELECT question_id FROM answers)");
    }
}
