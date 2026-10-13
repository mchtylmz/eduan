<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('languages')->where('code', 'nl')->exists()) {
            return;
        }
        $defaults = json_decode(File::get(lang_path('nl.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach (File::files(lang_path('nl')) as $file) {
            if ($file->getExtension() === 'php') {
                // A whole PHP group (e.g. __('upload')) is an array, never a JSON label.
                unset($defaults[$file->getFilenameWithoutExtension()]);
                foreach (Arr::dot(require $file->getPathname()) as $key => $value) {
                    $defaults[$file->getFilenameWithoutExtension().'.'.$key] = $value;
                }
            }
        }
        DB::transaction(function () use ($defaults) {
            $existing = DB::table('translations')->where('locale', 'nl')->pluck('value', 'key')->all();
            foreach (array_chunk($defaults, 200, true) as $chunk) {
                $rows = [];
                foreach ($chunk as $key => $value) {
                    if (! is_string($value) || blank($value) || mb_strlen($key) > 255) {
                        continue;
                    }
                    if (! array_key_exists($key, $existing)) {
                        $rows[] = ['locale' => 'nl', 'key' => $key, 'value' => $value];
                    } elseif (blank($existing[$key])) {
                        DB::table('translations')->where('locale', 'nl')->where('key', $key)
                            ->where(fn ($query) => $query->whereNull('value')->orWhereRaw("TRIM(value) = ''"))
                            ->update(['value' => $value]);
                    }
                }
                if ($rows) {
                    DB::table('translations')->insertOrIgnore($rows);
                }
            }
        });
    }

    public function down(): void
    {
        // Translations may have been edited since the fill; never delete or overwrite them.
    }
};
