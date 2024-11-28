<?php

namespace App\Helpers;

use App\Models\Translation;
use Druc\Langscanner\CachedFileTranslations;
use Druc\Langscanner\FileTranslations;
use Druc\Langscanner\MissingTranslations;
use Druc\Langscanner\RequiredTranslations;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TranslationHelper
{
    public function exists(string $locale): bool
    {
        return file_exists(lang_path($locale) . '.json');
    }

    public function scan(string $locale): array
    {
        $fileTranslations = new CachedFileTranslations(new FileTranslations(['language' => $locale]));

        $missingTranslations = new MissingTranslations(
            new RequiredTranslations(config('langscanner')),
            $fileTranslations
        );

        return array_fill_keys(
            array_keys($missingTranslations->all()),
            ''
        );
    }

    public static function upload(string $locale, array $translations = [])
    {
        $translations = collect($translations)->map(function ($value, $key) use($locale) {
            return [
                'locale' => $locale,
                'value' => $value,
                'key' => $key,
            ];
        })->values()->toArray();

        Translation::insertOrIgnore($translations);
        //Translation::upsert($translations, uniqueBy: ['locale', 'key'], update: ['key']);
    }

    public static function build(string $locale, array $translations = []): void
    {
        $translations = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        resolve(Filesystem::class)->put(lang_path($locale) . '.json', $translations);
    }
}
