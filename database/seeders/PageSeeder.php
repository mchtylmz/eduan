<?php

namespace Database\Seeders;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::create([
            'slug' => 'home',
            'title' => [
                'nl' => 'Home',
                'tr' => 'Anasayfa'
            ],
            'brief' => [
                'nl' => 'sayfa hakkında kısa açıklama',
                'tr' => 'ssayfa hakkında kısa açıklama'
            ],
            'content' => [
                'nl' => [],
                'tr' => [
                    'welcomeTitle' => 'Slogan veya İlgi Çekici Cümle Buraya Gelecek',
                    'image' => 'uploads/banner-bg.png',
                    'search' => StatusEnum::ACTIVE->value,

                    'lessonTitle' => 'Dersler Başlık',
                    'lessonStatus' => StatusEnum::ACTIVE->value,

                    'testTitle' => 'Testler Başlık',
                    'testStatus' => StatusEnum::ACTIVE->value,

                    'faqImage' => 'uploads/banner-bg.png',
                    'faqTitle' => 'Sıkça Sorulan Sorular Başlık',
                    'faqStatus' => StatusEnum::ACTIVE->value,
                ]
            ],
            'keywords' => [
                'nl' => 'anahtar,kelime',
                'tr' => 'anahtar,kelime'
            ],
            'sort' => 1,
            'menu' => PageMenuEnum::HOME,
            'type' => PageTypeEnum::SYSTEM,
            'status' => StatusEnum::ACTIVE
        ]);
    }
}
