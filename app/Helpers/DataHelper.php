<?php

namespace App\Helpers;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\YesNoEnum;
use App\Models\Contact;
use App\Models\ExamReview;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class DataHelper
{
    protected bool $cacheStatus = true;
    protected string $cachePrefix = 'data_';
    protected int $cacheTime = 60 * 60 * 24;

    public function lessons(bool $active = true, bool $hits = false, int $limit = 10): LengthAwarePaginator
    {
        return Cache::remember(
            $this->cacheKey('lessons', $active, $hits, $limit),
            $this->cacheTime($hits ? 1 : 7),
            fn() => Lesson::when($active, fn($query) => $query->active())
                ->when($hits, fn($query) => $query->withCount('topics')->orderByDesc('hits'))
                ->orderBy('sort')
                ->paginate($limit)
        );
    }

    public function topics(bool $active = true, bool $hits = false, int $limit = 10): LengthAwarePaginator
    {
        return Cache::remember(
            $this->cacheKey('topics', $active, $hits, $limit),
            $this->cacheTime($hits ? 1 : 7),
            fn() => Topic::when($active, fn($query) => $query->active())
                ->when($hits, fn($query) => $query->withCount('questions')->orderByDesc('hits'))
                ->orderBy('sort')
                ->paginate($limit)
        );
    }

    public function defaultLanguage(): Language
    {
        $defaultLocale = settings()->defaultLocale;

        return Cache::remember(
            $this->cacheKey('defaultLanguage', $defaultLocale),
            $this->cacheTime(365),
            fn() => Language::when($defaultLocale, fn($query) => $query->where('code', $defaultLocale))
                ->active()
                ->first() ??
                Language::active()
                    ->first()
        );
    }

    public function language(string $code): Language
    {
        return Cache::remember(
            $this->cacheKey('language', $code),
            $this->cacheTime(365),
            fn() => Language::where('code', $code)->first() ?? $this->defaultLanguage()
        );
    }

    public function languages(bool $active = false): Collection
    {
        return Cache::remember(
            $this->cacheKey('languages', $active),
            $this->cacheTime(365),
            fn() => Language::when($active, fn($query) => $query->active())->orderBy('id')->get()
        );
    }

    public function setting(string $key, ?string $locale = null): ?string
    {
        return Cache::remember(
            $this->cacheKey('setting', $key, $locale),
            $this->cacheTime(365),
            fn() => settings()->{$key . '_' . $locale} ?? null
        );
    }

    public function countExamReviewsNotRead(): int
    {
        return Cache::remember(
            $this->cacheKey('countExamReviewsNotRead'),
            60 * 20, // 20 dakika
            fn() => ExamReview::where('has_read', YesNoEnum::NO)->count()
        );
    }

    public function countContactMessageNotRead(): int
    {
        return Cache::remember(
            $this->cacheKey('countContactMessageNotRead'),
            $this->cacheTime(365),
            fn() => Contact::where('has_read', YesNoEnum::NO)->count()
        );
    }

    public function footerPages(): Collection
    {
        return Cache::remember(
            $this->cacheKey('footerPages'),
            $this->cacheTime(365),
            fn() => Page::active()
                ->where('type', PageTypeEnum::CUSTOM)
                ->where('menu', PageMenuEnum::FOOTER)
                ->orderBy('sort')
                ->get()
        );
    }

    public function filters(): FilterHelper
    {
        return new FilterHelper();
    }

    public function setCacheStatus(bool $status): DataHelper
    {
        $this->cacheStatus = $status;
        return $this;
    }

    public function setCacheTime(int $time): DataHelper
    {
        $this->cacheTime = $time;
        return $this;
    }

    protected function cacheTime(int $time): int
    {
        if ($this->cacheStatus === false) {
            return 0;
        }

        return $this->cacheTime * $time;
    }

    protected function cacheKey(...$args): ?string
    {
        $args = collect($args)->map(fn($arg) => is_string($arg) ? $arg : intval($arg))->implode('_');

        if ($this->cacheStatus === false) {
            Cache::forget($this->cachePrefix . $args);
        }

        return $this->cachePrefix . $args;
    }
}
