<?php

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Language;

if (!function_exists('user')) {
    /**
     * @param null $key
     * @return Authenticatable|null
     */
    function user($key = null): ?Authenticatable
    {
        if ($key) {
            return auth()->user()?->{$key} ?? null;
        }

        return auth()?->user();
    }
}

if (!function_exists('singular')) {
    /**
     * @param string $text
     * @return string
     */
    function singular(string $text): string
    {
        return str_replace(
            ['rleri', 'leri', 'ler', 'ları', 'lar'],
            ['ri', 'si', '', 'sı', ''],
            Str::singular($text)
        );
    }
}

if (!function_exists('letters')) {
    /**
     * @param int|null $index
     * @return string|array
     */
    function letters(?int $index = null): string|array
    {
        $letters = range('A', 'Z');
        if (!is_null($index)) {
            return $letters[$index] ?? 'A';
        }

        return $letters;
    }
}

if (!function_exists('data')) {

    /**
     * @return \App\Helpers\DataHelper
     */
    function data(): \App\Helpers\DataHelper
    {
        return new \App\Helpers\DataHelper();
    }
}

if (!function_exists('getJsonLocaleValue')) {
    /**
     * @param $value
     * @return string
     */
    function getJsonLocaleValue($value): string
    {
        return json_decode($value, true)[app()->getLocale()] ?? '';
    }
}

if (!function_exists('getImage')) {
    /**
     * @param string|null $image
     * @param string|null $default
     * @return string
     */
    function getImage(?string $image, ?string $default = null): string
    {
        if (!is_file(public_path($image))) {
            return asset($default ?: settings()->siteLogo);
        }

        if (file_exists(public_path($image))) {
            return url($image);
        }

        return asset($image);
    }
}

if (!function_exists('dateFormat')) {

    /**
     * @param string $value
     * @param string $format
     * @return string
     */
    function dateFormat(?string $value, string $format = 'd/m/Y, l'): string
    {
        if (!is_string($value)) {
            return '';
        }
        return Carbon::parse($value)->timezone(settings()->timezone ?? config('app.timezone'))->translatedFormat($format);
    }
}

if (!function_exists('timeAgo')) {
    /**
     * @param string|null $value
     * @return string
     */
    function timeAgo(?string $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        return Carbon::parse($value)->timezone(settings()->timezone ?? config('app.timezone'))->diffForHumans();
    }
}

if (!function_exists('settingLocale')) {
    /**
     * @param string $key
     * @param string|null $locale
     * @return string|null
     */
    function settingLocale(string $key, ?string $locale = null): ?string
    {
        return data()->setting($key, $locale ?? app()->getLocale());
    }
}

if (!function_exists('routeIs')) {
    /**
     * @param ...$patterns
     * @return bool
     */
    function routeIs(...$patterns): bool
    {
        return request()->routeIs($patterns);
    }
}

if (!function_exists('incrementIf')) {

    /**
     * @param bool $condition
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @param string $column
     * @return void
     */
    function incrementIf(bool $condition, ?\Illuminate\Database\Eloquent\Model $model = null, string $column = 'hits'): void
    {
        if ($condition) $model->increment($column);
    }
}

if (!function_exists('flush')) {
    /**
     * @return void
     */
    function flush(): void
    {
        cache()->flush();
        cache()->clear();
    }
}

if (!function_exists('resetCache')) {
    /**
     * @return void
     */
    function resetCache(): void
    {
        cache()->flush();
        cache()->clear();
        Artisan::call('optimize:clear');
    }
}

if (!function_exists('randomBackgroundClass')) {
    /**
     * @return string
     */
    function randomBackgroundClass(): string
    {
        return Arr::random([
            'bg-alt-primary',
            'bg-alt-secondary',
            'bg-alt-success',
            'bg-alt-danger',
            'bg-alt-warning',
            'bg-alt-info',
            'bg-primary',
            'bg-secondary',
            'bg-success',
            'bg-danger',
            'bg-warning',
            'bg-info',
            'bg-dark',
            'bg-body-light',
        ]);
    }
}

if (!function_exists('replaceEmailVariables')) {

    /**
     * @param string $content
     * @param array $values
     * @return string
     */
    function replaceEmailVariables(string $content, array $values): string
    {
        $fields = [
            '[ad]' => 'name',
            '[soyad]' => 'surname',
            '[ad_soyad]' => 'display_name',
            '[email]' => 'email',
            '[phone]' => 'phone',
            '[telefon]' => 'phone',
            '[okul_adi]' => 'school_name',
            '[kayit_tarihi]' => 'created_at',
        ];
        $fields = collect($fields)->mapWithKeys(function ($fieldValue, $fieldKey) use ($values) {
            if ($fieldValue == 'display_name' && !data_get($values, $fieldValue)) {
                return [$fieldKey => sprintf(
                    '%s %s',
                    data_get($values, 'name') ?? '', data_get($values, 'surname') ?? ''
                )];
            }
            return [$fieldKey => data_get($values, $fieldValue) ?? ''];
        })->toArray();

        return Str::replace(
            [...array_keys($fields), '<pre>', '</pre>'],
            array_values($fields),
            $content
        );
    }
}
