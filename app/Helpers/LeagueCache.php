<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class LeagueCache
{
    public static function resultsKey(int $leagueId, int $ranking, int $capacity): string
    {
        $revision = cache()->rememberForever('league:revision:'.$leagueId, fn () => (string) Str::uuid());

        return "league_{$leagueId}_{$ranking}_{$capacity}_{$revision}";
    }

    public static function invalidate(int $leagueId): void
    {
        cache()->forget('leagues_first');
        cache()->forget('leagues_all');
        cache()->forever('league:revision:'.$leagueId, (string) Str::uuid());
        FrontendCache::invalidate('sitemap');
    }
}
