<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Pharaonic\Laravel\Settings\Models\Settings;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Settings::insert([
            ['name' => 'appName', 'value' => 'HYPOTENUSE'],
            ['name' => 'siteFavicon', 'value' => 'uploads/favicon.png'],
            ['name' => 'siteLogo', 'value' => 'uploads/logo.png'],
            ['name' => 'siteLogoWhite', 'value' => 'uploads/logo.png'],
            ['name' => 'defaultLocale', 'value' => 'tr'],
            ['name' => 'primaryColor', 'value' => '#ED1B24'],
            ['name' => 'secondaryColor', 'value' => '#EFEFEF'],
            ['name' => 'thirdColor', 'value' => 'orange'],
            ['name' => 'fourthColor', 'value' => 'pink'],
            ['name' => 'fifthColor', 'value' => 'green'],
            ['name' => 'chartColor', 'value' => 'blue'],
            ['name' => 'registerStatus', 'value' => 'active'],
            ['name' => 'multiLanguage', 'value' => 'active'],
            ['name' => 'mailHost', 'value' => 'sandbox.smtp.mailtrap.io'],
            ['name' => 'mailPort', 'value' => 2525],
            ['name' => 'mailEncryptionType', 'value' => 'TLS'],
            ['name' => 'mailUsername', 'value' => '16be0a148786aa'],
            ['name' => 'mailPassword', 'value' => 'a6f05fc460cf28'],
            ['name' => 'mailFromEmail', 'value' => 'info@localhost.com'],
            ['name' => 'mailFromName', 'value' => 'HYPOTENUSE'],
            ['name' => 'timezone', 'value' => 'Europe/Amsterdam'],
            ['name' => 'coverAuth', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverBlog', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverContact', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverLessons', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverTopics', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverTests', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverAccount', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverFaq', 'value' => 'uploads/breadcrumb-bg.jpg'],
            ['name' => 'coverPage', 'value' => 'uploads/breadcrumb-bg.jpg'],
        ]);

        foreach (Language::all() as $language) {
            Settings::insert([
                ['name' => 'siteTitle_' . $language->code, 'value' => 'HYPOTENUSE ' . $language->code],
                ['name' => 'siteDescription_' . $language->code, 'value' => fake()->text(60)],
                ['name' => 'siteKeywords_' . $language->code, 'value' => implode(',', fake()->words(5))]
            ]);
        }

    }
}
