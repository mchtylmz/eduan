<?php

namespace App\Helpers;

use App\Models\Translation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class TranslationHelper
{
    public function exists(string $locale): bool
    {
        return file_exists(lang_path($locale).'.json');
    }

    public static function upload(string $locale, array $translations = []): void
    {
        $translations = collect($translations)->map(function ($value, $key) use ($locale) {
            return [
                'locale' => $locale,
                'value' => $value,
                'key' => $key,
            ];
        })->values()->toArray();

        // Translation::insertOrIgnore($translations);
        Translation::upsert($translations, uniqueBy: ['locale', 'key'], update: ['key']);
    }

    public static function build(string $locale, array $translations = []): void
    {
        abort_unless(preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z]{2,4})?$/D', $locale), 422);
        $filesystem = resolve(Filesystem::class);
        $groups = (new TranslationScanner)->groups();
        $jsonFile = lang_path($locale.'.json');
        $json = is_file($jsonFile) ? json_decode($filesystem->get($jsonFile), true, 512, JSON_THROW_ON_ERROR) : [];
        $php = [];
        foreach (array_replace($json, $translations) as $key => $value) {
            if (isset($groups[$key])) {
                unset($json[$key]);
                if (filled($value)) {
                    $group = $groups[$key]['group'];
                    if (! isset($php[$group])) {
                        $file = lang_path($locale.'/'.$group.'.php');
                        $php[$group] = is_file($file) ? require $file : [];
                    }
                    Arr::set($php[$group], substr($key, strlen($group) + 1), $value);
                }
            } elseif (filled($value)) {
                $json[$key] = $value;
            }
        }
        $translations = $json;
        // Keep the phone field translated when the panel rebuilds an incomplete JSON file.
        $defaults = lang_path($locale.'/account.php');
        if (is_file($defaults)) {
            $labels = require $defaults;
            $phone = $translations['Telefon Numarası'] ?? '';
            if (blank($phone) || ($locale !== 'tr' && $phone === 'Telefon Numarası')) {
                $translations['Telefon Numarası'] = $labels['phone'];
            }
        }
        $translations = json_encode((object) $translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        foreach ($php as $group => $labels) {
            $filesystem->ensureDirectoryExists(lang_path($locale));
            $filesystem->replace(lang_path($locale.'/'.$group.'.php'), "<?php\n\nreturn ".var_export($labels, true).";\n");
        }
        $filesystem->replace($jsonFile, $translations."\n");
        app('translator')->setLoaded([]);
        FrontendCache::invalidate('languages', 'navigation', 'lessons', 'topics', 'tests', 'exams', 'blogs', 'faqs', 'pages', 'sitemap');
    }
}
