<?php

namespace App\Helpers;

use App\Models\Blog;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Test;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FrontendSeo
{
    public static function text(?string $value, int $limit = 160): string
    {
        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value ?? '');
        $value = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $value)), $limit, '…');
    }

    public static function baseUrl(): string
    {
        return rtrim(config('app.url'), '/');
    }

    public static function localizedUrl(string $url, string $locale, int $page = 1): string
    {
        // The locale is stored in the session, so even the default must be explicit.
        $query = ['lang' => $locale];
        if ($page > 1) {
            $query['page'] = $page;
        }

        $url = self::baseUrl().(parse_url($url, PHP_URL_PATH) ?: '');

        return $url.($query ? '?'.http_build_query($query) : '');
    }

    public static function locales(?Model $record = null): array
    {
        $codes = data()->languages(active: true)->pluck('code')->all();
        if ($record instanceof Blog || $record instanceof Exam || $record instanceof Test) {
            return array_values(array_intersect($codes, [$record->locale]));
        }
        $field = match (true) {
            $record instanceof Lesson => 'name',
            $record instanceof Topic, $record instanceof Page => 'title',
            default => null,
        };
        if ($field) {
            $translated = array_keys(array_filter($record->getTranslations($field), fn ($value) => is_string($value) && trim($value) !== ''));

            return array_values(array_intersect($codes, $translated));
        }

        return $codes;
    }

    public static function metadata(?string $title = null, ?Page $page = null): array
    {
        $route = request()->route()?->getName();
        $record = collect(request()->route()?->parameters() ?? [])->first(fn ($value) => $value instanceof Model) ?? $page;
        $locale = $record instanceof Blog || $record instanceof Exam || $record instanceof Test ? $record->locale : app()->getLocale();
        $available = self::locales($record);
        if ($record && $available && ! in_array($locale, $available)) {
            $fallback = config('app.fallback_locale');
            $locale = in_array($fallback, $available) ? $fallback : $available[0];
        }
        $brand = self::text(settingLocale('siteTitle'), 100) ?: config('app.name');
        $heading = self::text($title ?: $brand, 160);
        $description = match (true) {
            $record instanceof Blog => $record->brief ?: $record->content,
            $record instanceof Exam, $record instanceof Test => $record->content,
            $record instanceof Lesson, $record instanceof Topic => $record->description,
            $record instanceof Page => $record->brief,
            default => null,
        };
        $description = self::text($description) ?: self::text($heading.'. '.settingLocale('siteDescription'));
        $keywords = self::text($record?->keywords ?: settingLocale('siteKeywords'), 250);
        $indexable = in_array($route, ['frontend.home', 'frontend.lessons', 'frontend.lesson', 'frontend.tests', 'frontend.test.detail', 'frontend.exams', 'frontend.exam.detail', 'frontend.blog', 'frontend.blog.detail', 'frontend.faqs', 'frontend.contact', 'frontend.leagues', 'frontend.leagues.detail', 'frontend.page']);
        if (($record instanceof Lesson || $record instanceof Topic || $record instanceof Page) && $available && ! in_array(app()->getLocale(), $available)) {
            $indexable = false;
        }
        if (request()->filled('search') || request()->filled('word') || request()->filled('q')) {
            $indexable = false;
        }
        $number = request()->query('page');
        $number = is_scalar($number) && ctype_digit((string) $number) ? max(1, (int) $number) : 1;
        $url = self::localizedUrl(request()->url(), $locale, $number);
        $alternates = [];
        if ($indexable) {
            foreach ($available as $code) {
                $alternates[$code] = self::localizedUrl(request()->url(), $code, $number);
            }
        }
        $image = $record instanceof Blog && $record->image ? getImage($record->image) : getImage(settings()->siteLogo);
        $image = $image ? (str_starts_with($image, 'http') ? $image : url($image)) : null;
        $canonical = in_array($route, ['frontend.create.password', 'frontend.verify', 'frontend.unsubscribe']) ? null : $url;
        if ($canonical !== null && ! $indexable && ! $record && ! request()->has('lang')) {
            $canonical = self::baseUrl().request()->getPathInfo();
        }

        return compact('heading', 'description', 'keywords', 'image', 'locale', 'indexable', 'canonical', 'alternates', 'record', 'brand') + [
            'title' => $heading === $brand ? $brand : $heading.' | '.$brand,
            'robots' => $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow',
            'type' => $record instanceof Blog ? 'article' : 'website',
        ];
    }
}
