<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FrontendCache
{
    public static function remember(string $resource, array $context, callable $load, ?callable $expires = null)
    {
        $store = Cache::store('frontend');
        $revision = $store->rememberForever('revision:'.$resource, fn () => (string) Str::uuid());
        $key = $resource.':'.hash('sha256', json_encode([config('app.version'), app()->getLocale(), $revision, $context]));
        $cached = $store->get($key);
        if ($cached !== null) {
            return $cached['value'];
        }
        $value = $load();
        $ttl = config('cache.frontend_ttl');
        if ($expires && ($deadline = $expires())) {
            $ttl = min($ttl, max(1, now()->diffInSeconds($deadline, false)));
        }
        $store->put($key, ['value' => $value], (int) $ttl);

        return $value;
    }

    public static function paginate(string $resource, array $context, callable $load, ?callable $expires = null)
    {
        // Free-text searches and deep pages must not create unlimited week-long files.
        $value = ! empty($context['search']) || ($context['page'] ?? 1) > 100
            ? $load()
            : self::remember($resource, $context, $load, $expires);

        return $value->setPath(Paginator::resolveCurrentPath());
    }

    public static function invalidate(string ...$resources): void
    {
        foreach (array_unique($resources) as $resource) {
            Cache::store('frontend')->forever('revision:'.$resource, (string) Str::uuid());
        }
    }

    public static function modelChanged(Model $model, bool $deleted = false): void
    {
        if (! $deleted && $model->wasChanged() && ! array_diff(array_keys($model->getChanges()), ['hits', 'updated_at'])) {
            return;
        }
        $resources = match ($model->getTable()) {
            'lessons', 'topics', 'questions', 'answers', 'exams' => ['lessons', 'topics', 'tests', 'navigation'],
            'exam_reviews' => ['tests'],
            'tests', 'tests_sections', 'tests_questions' => ['exams'],
            'blogs' => ['blogs'],
            'leagues', 'league_results' => ['sitemap'],
            'faqs' => ['faqs'],
            'pages' => ['pages', 'navigation'],
            'languages', 'translations' => ['languages', 'navigation', 'lessons', 'topics', 'tests', 'exams', 'blogs', 'faqs', 'pages'],
            default => [],
        };
        if ($resources) {
            self::invalidate(...array_unique([...$resources, 'sitemap']));
        }
    }
}
