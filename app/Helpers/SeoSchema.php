<?php

namespace App\Helpers;

use App\Models\Blog;
use App\Models\Exam;
use App\Models\Test;

class SeoSchema
{
    public static function graph(array $seo): array
    {
        if (! $seo['indexable']) {
            return [];
        }
        $root = FrontendSeo::baseUrl();
        $organization = ['@type' => 'EducationalOrganization', '@id' => $root.'/#organization', 'name' => $seo['brand'], 'url' => $root];
        if ($logo = settings()->siteLogo) {
            $organization['logo'] = url(getImage($logo));
        }
        $website = ['@type' => 'WebSite', '@id' => $root.'/#website', 'name' => $seo['brand'], 'url' => $root, 'publisher' => ['@id' => $organization['@id']]];
        $page = ['@type' => request()->routeIs('frontend.contact') ? 'ContactPage' : 'WebPage', '@id' => $seo['canonical'].'#webpage', 'url' => $seo['canonical'], 'name' => $seo['heading'], 'description' => $seo['description'], 'inLanguage' => $seo['locale'], 'isPartOf' => ['@id' => $website['@id']]];
        $graph = [$organization, $website, $page];
        if (! request()->routeIs('frontend.home')) {
            $items = [['name' => __('Anasayfa'), 'item' => FrontendSeo::localizedUrl($root, app()->getLocale())]];
            $parent = match (true) {
                request()->routeIs('frontend.blog.detail') => ['name' => __('Blog'), 'item' => route('frontend.blog')],
                request()->routeIs('frontend.test.detail') => ['name' => __('Testler'), 'item' => route('frontend.tests')],
                request()->routeIs('frontend.exam.detail') => ['name' => __('Sınavlar'), 'item' => route('frontend.exams')],
                request()->routeIs('frontend.lesson') => ['name' => __('Dersler'), 'item' => route('frontend.lessons')],
                request()->routeIs('frontend.leagues.detail') => ['name' => __('Ligler'), 'item' => route('frontend.leagues')],
                default => null,
            };
            if ($parent) {
                $parent['item'] = FrontendSeo::localizedUrl($parent['item'], app()->getLocale());
                $items[] = $parent;
            }
            $items[] = ['name' => $seo['heading'], 'item' => $seo['canonical']];
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1] + $item, $items, array_keys($items))];
        }
        $record = $seo['record'];
        if ($record instanceof Blog) {
            $article = ['@type' => 'BlogPosting', 'headline' => $seo['heading'], 'description' => $seo['description'], 'inLanguage' => $record->locale, 'mainEntityOfPage' => ['@id' => $page['@id']], 'publisher' => ['@id' => $organization['@id']], 'datePublished' => $record->published_at?->toIso8601String(), 'dateModified' => $record->updated_at?->toIso8601String()];
            if ($seo['image']) {
                $article['image'] = $seo['image'];
            }
            $graph[] = array_filter($article, fn ($value) => $value !== null);
        } elseif ($record instanceof Exam || $record instanceof Test) {
            $graph[] = ['@type' => 'LearningResource', 'name' => $record->name, 'description' => $seo['description'], 'url' => $seo['canonical'], 'inLanguage' => $record->locale, 'learningResourceType' => $record instanceof Exam ? 'practice test' : 'exam'];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
