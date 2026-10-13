<?php

namespace App\Helpers;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Contact;
use App\Models\ExamReview;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Season;
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
        return $this->rememberPublic('navigation', ['lessons', $active, $hits, $limit],
            fn () => Lesson::select(['id', 'code', 'name', 'sort', 'hits', 'status'])
                ->when($active, fn ($query) => $query->active())
                ->when($hits, fn ($query) => $query->withCount('topics')->orderByDesc('hits'))
                ->orderBy('sort')->paginate($limit, ['*'], 'navigation_lessons', 1)
        );
    }

    public function topics(bool $active = true, bool $hits = false, int $limit = 10): LengthAwarePaginator
    {
        return $this->rememberPublic('navigation', ['topics', $active, $hits, $limit],
            fn () => Topic::select(['id', 'code', 'title', 'sort', 'hits', 'status'])
                ->when($active, fn ($query) => $query->active())
                ->when($hits, fn ($query) => $query->withCount('questions')->orderByDesc('hits'))
                ->orderBy('sort')->paginate($limit, ['*'], 'navigation_topics', 1)
        );
    }

    public function defaultLanguage(): Language
    {
        $defaultLocale = settings()->defaultLocale;

        return $this->rememberPublic('languages', ['default', $defaultLocale],
            fn () => Language::when($defaultLocale, fn ($query) => $query->where('code', $defaultLocale))
                ->active()->first() ?? Language::active()->first()
        );
    }

    public function language(string $code): Language
    {
        return $this->rememberPublic('languages', ['language', $code],
            fn () => Language::where('code', $code)->first() ?? $this->defaultLanguage()
        );
    }

    public function languages(bool $active = false): Collection
    {
        return $this->rememberPublic('languages', ['languages', $active],
            fn () => Language::when($active, fn ($query) => $query->active())->orderBy('id')->get()
        );
    }

    public function setting(string $key, ?string $locale = null): ?string
    {
        return $this->rememberPublic('settings', [$key, $locale],
            fn () => settings()->{$key.'_'.$locale} ?? null
        );
    }

    public function countExamReviewsNotRead(): int
    {
        return Cache::remember(
            $this->cacheKey('countExamReviewsNotRead'),
            60 * 20, // 20 dakika
            fn () => ExamReview::where('has_read', YesNoEnum::NO)->count()
        );
    }

    public function countContactMessageNotRead(): int
    {
        return Cache::remember(
            $this->cacheKey('countContactMessageNotRead'),
            $this->cacheTime(365),
            fn () => Contact::where('has_read', YesNoEnum::NO)->count()
        );
    }

    public function footerPages(): Collection
    {
        return $this->rememberPublic('navigation', ['footer-pages'],
            fn () => Page::select(['id', 'slug', 'title', 'sort', 'status', 'type', 'menu'])
                ->active()->where('type', PageTypeEnum::CUSTOM)->where('menu', PageMenuEnum::FOOTER)
                ->orderBy('sort')->get()
        );
    }

    private function rememberPublic(string $resource, array $context, callable $load)
    {
        return $this->cacheStatus
            ? FrontendCache::remember($resource, $context, $load, fn () => now()->addSeconds($this->cacheTime(7)))
            : $load();
    }

    public function seasons(): array
    {
        return Cache::remember(
            $this->cacheKey('seasons'),
            $this->cacheTime(30),
            fn () => Season::orderBy('id', 'desc')
                ->where('status', StatusEnum::ACTIVE->value)
                ->get()
                ->keyBy('id')
                ->map(fn ($item) => sprintf(
                    '%s - %s',
                    dateFormat($item->start_date, 'd M Y'),
                    dateFormat($item->end_date, 'd M Y'),
                ))
                ->toArray()
        );
    }

    public function filters(): FilterHelper
    {
        return new FilterHelper;
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
        $args = collect($args)->map(fn ($arg) => is_string($arg) ? $arg : intval($arg))->implode('_');

        if ($this->cacheStatus === false) {
            Cache::forget($this->cachePrefix.$args);
        }

        return $this->cachePrefix.$args;
    }
}
