<?php

namespace App\Helpers;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\Faq;
use App\Models\League;
use App\Models\Lesson;
use App\Models\Page;
use App\Models\Test;
use App\Models\Topic;
use Illuminate\Support\Carbon;

class SeoSitemap
{
    public static function entries(): array
    {
        return FrontendCache::remember('sitemap', [FrontendSeo::baseUrl()], function () {
            $entries = [];
            $add = function (string $url, array $locales, $modified = null) use (&$entries) {
                $alternates = [];
                foreach ($locales as $locale) {
                    $alternates[$locale] = FrontendSeo::localizedUrl($url, $locale);
                }
                foreach ($alternates as $loc) {
                    $entries[] = ['loc' => $loc, 'alternates' => $alternates, 'lastmod' => $modified ? Carbon::parse($modified)->toIso8601String() : null];
                }
            };
            $home = Page::where('menu', PageMenuEnum::HOME)->first(['id', 'title', 'updated_at']);
            $add(route('frontend.home'), FrontendSeo::locales($home), $home?->updated_at);
            foreach (['lessons' => Lesson::class, 'tests' => Exam::class, 'exams' => Test::class, 'blog' => Blog::class, 'faqs' => Faq::class] as $route => $model) {
                $query = $model::active();
                if ($model === Blog::class) {
                    $query->where('published_at', '<=', now());
                }
                $add(route('frontend.'.$route), FrontendSeo::locales(), $query->max('updated_at'));
            }
            foreach (['contact'] as $route) {
                $add(route('frontend.'.$route), FrontendSeo::locales());
            }
            foreach (Page::active()->where('menu', PageMenuEnum::FOOTER)->where('type', PageTypeEnum::CUSTOM)->get(['id', 'slug', 'title', 'link', 'updated_at']) as $page) {
                $locales = array_filter(FrontendSeo::locales($page), fn ($locale) => trim($page->getTranslation('link', $locale, false) ?? '') === '');
                $add(route('frontend.page', $page->slug), $locales, $page->updated_at);
            }
            foreach (Blog::active()->where('published_at', '<=', now())->get(['id', 'slug', 'locale', 'updated_at']) as $blog) {
                $add(route('frontend.blog.detail', $blog->slug), FrontendSeo::locales($blog), $blog->updated_at);
            }
            foreach (Lesson::active()->get(['id', 'code', 'name', 'updated_at']) as $lesson) {
                $add(route('frontend.lesson', $lesson->code), FrontendSeo::locales($lesson), $lesson->updated_at);
            }
            foreach (Topic::active()->whereHas('lesson', fn ($query) => $query->active())->get(['id', 'code', 'title', 'updated_at']) as $topic) {
                $add(route('frontend.tests', $topic->code), FrontendSeo::locales($topic), $topic->updated_at);
            }
            foreach ([Exam::class => 'frontend.test.detail', Test::class => 'frontend.exam.detail'] as $model => $route) {
                foreach ($model::active()->get(['id', 'code', 'locale', 'updated_at']) as $record) {
                    $add(route($route, $record->code), FrontendSeo::locales($record), $record->updated_at);
                }
            }
            if (settings()->leagueStatus === 'active') {
                $add(route('frontend.leagues'), FrontendSeo::locales());
            }
            foreach (League::when(settings()->leagueStatus !== 'active', fn ($query) => $query->whereRaw('1 = 0'))->get(['id', 'code', 'updated_at']) as $league) {
                $add(route('frontend.leagues.detail', $league->code), FrontendSeo::locales(), $league->updated_at);
            }

            return $entries;
        }, fn () => Blog::active()->where('published_at', '>', now())->min('published_at'));
    }
}
