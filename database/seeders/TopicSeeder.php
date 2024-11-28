<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Topic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Topic::factory()->count(60)->create();

        foreach (Topic::all() as $topic) {
            foreach (Language::all() as $language) {
                $topic->setTranslation('title', $language->code, sprintf(
                    'Konu %d', $topic->sort
                ));
                $topic->setTranslation('description', $language->code, fake()->text(120));
            }

            $topic->save();
        }
    }
}
