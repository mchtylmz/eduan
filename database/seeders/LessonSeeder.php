<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lesson::factory(15)->create();

        foreach (Lesson::all() as $lesson) {

            foreach (Language::all() as $language) {
                $lesson->setTranslation('name', $language->code, sprintf(
                    'Ders %d', $lesson->sort
                ));
                $lesson->setTranslation('description', $language->code, fake()->text(120));
            }

            $lesson->save();
        }

    }
}
