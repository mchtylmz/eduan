<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TableCache
{
    public static function remember(string $table, array $parameters, callable $load, array $scope = []): array
    {
        $refresh = (bool) ($parameters['refresh'] ?? false);
        unset($parameters['refresh']);
        ksort($parameters);
        foreach (['topics', 'lessons', 'roles', 'email_verified', 'users', 'exams', 'tests', 'has_read'] as $filter) {
            if (isset($parameters[$filter]) && is_array($parameters[$filter])) {
                sort($parameters[$filter]);
            }
        }
        $key = self::key($table, [$parameters, $scope]);
        if ($refresh) {
            $value = $load();
            Cache::put($key, $value, 60);

            return $value;
        }

        return Cache::remember($key, 60, $load);
    }

    public static function options(string $name, callable $load): array
    {
        return Cache::remember(self::key('options', [$name]), 3600, $load);
    }

    public static function invalidate(string ...$tables): void
    {
        foreach ($tables as $table) {
            Cache::forever('admin-table:revision:'.$table, (string) Str::uuid());
        }
    }

    private static function key(string $table, array $context): string
    {
        $actor = auth()->user();
        $revision = Cache::rememberForever('admin-table:revision:'.$table, fn () => (string) Str::uuid());
        $permissions = $actor?->getAllPermissions()->pluck('name')->sort()->values()->all() ?? [];

        return 'admin-table:'.$table.':'.hash('sha256', json_encode([
            config('app.version'), $revision, app()->getLocale(), $actor?->id, $permissions, $context,
        ]));
    }
}
