<?php

namespace Database\Seeders;

use App\Enums\YesNoEnum;
use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::factory()->create([
            'code' => 'tr',
            'name' => 'Türkçe'
        ]);
        Language::factory()->create([
            'code' => 'nl',
            'name' => 'Flemenkçe'
        ]);
    }
}
