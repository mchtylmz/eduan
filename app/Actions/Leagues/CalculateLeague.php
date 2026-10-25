<?php

namespace App\Actions\Leagues;

use App\Enums\YesNoEnum;
use App\Helpers\LeaguePeriod;
use App\Helpers\LeagueRules;
use App\Helpers\TableCache;
use App\Models\League;
use App\Models\LeagueResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

class CalculateLeague
{
    use AsAction;

    public function handle(string $start, string $end, bool $live = false, ?int $leagueId = null): ?League
    {
        if (settings()->leagueStatus === 'passive') {
            throw new RuntimeException('League calculation is disabled.');
        }

        $selected = $leagueId !== null ? League::findOrFail($leagueId) : null;
        if ($selected && (! $selected->start_at || ! $selected->end_at)) {
            throw new InvalidArgumentException('League dates are missing.');
        }
        $period = $selected
            ? new LeaguePeriod(CarbonImmutable::instance($selected->start_at), CarbonImmutable::instance($selected->end_at))
            : LeaguePeriod::fromStrings($start, $end);
        $source = $selected?->label ?? LeagueRules::sourceForModel(settings()->leagueResultModel ?? 'App\\Models\\ExamResultDetail');
        $connection = DB::connection();
        $key = 'league:'.sha1($connection->getDatabaseName().'|'.$period->lockKey($source));
        // MySQL advisory locks also serialize creation before a league row exists.
        // They survive cache:clear and are released on connection loss.
        if ((int) $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$key])->acquired !== 1) {
            throw new RuntimeException('League calculation is already running.');
        }

        try {
            $league = $connection->transaction(function () use ($period, $source, $live, $leagueId) {
                $league = League::where('label', $source)
                    ->where('start_at', $period->start)->where('end_at', $period->end)
                    ->lockForUpdate()->first();
                if ($leagueId !== null && $league?->id !== $leagueId) {
                    throw new RuntimeException('League changed before calculation.');
                }
                // An explicit new-live request must not silently convert historical records.
                if ($live && $league && ! $league->is_live) {
                    throw new InvalidArgumentException('A single calculation already exists for this period.');
                }
                $isLive = $live || (bool) $league?->is_live;
                $rules = $league?->is_live
                    ? LeagueRules::fromSnapshot($source, $league->calculation_settings ?? [])
                    : new LeagueRules($source, (int) (settings()->leagueCorrectPoint ?? 3),
                        (int) (settings()->leagueIncorrectPoint ?? -1), (int) (settings()->leagueUserCount ?? 10));
                $through = $isLive ? $period->cutoff(CarbonImmutable::now()) : $period->end;
                $results = $through ? $this->results($rules, $period, $through) : collect();
                // Preserve the established no-data behavior for single calculations.
                if (! $isLive && $results->isEmpty()) {
                    return null;
                }
                $league ??= new League(['code' => 'hyp', 'label' => $source,
                    'start_at' => $period->start, 'end_at' => $period->end]);
                if ($isLive && ! $league->exists) {
                    $league->fill(['is_live' => true, 'calculation_settings' => $rules->snapshot()]);
                }
                if ($isLive) {
                    // Avoid broad legacy cache flushing for recurring live calculations.
                    if ($league->exists) {
                        League::insertLog($league, 'edit');
                    }
                    $league->saveQuietly();
                } else {
                    $league->save();
                }
                if ($league->wasRecentlyCreated) {
                    $league->code = 'hyp'.$league->id;
                }

                $timestamp = CarbonImmutable::now();
                $data = $results->map(fn ($result) => [
                    'league_id' => $league->id, 'user_id' => $result->user_id,
                    'total_score' => $result->total_score, 'total_correct' => $result->total_correct,
                    'total_incorrect' => $result->total_incorrect, 'total_duration' => $result->total_duration,
                    'rank_position' => $result->rank_position,
                    'position' => (($result->rank_position - 1) % $rules->userCount) + 1,
                    'league_number' => (int) ceil($result->rank_position / $rules->userCount),
                    'created_at' => $timestamp, 'updated_at' => $timestamp,
                ]);
                foreach ($data->chunk(500) as $chunk) {
                    LeagueResult::upsert($chunk->all(), ['league_id', 'user_id'],
                        ['total_score', 'total_correct', 'total_incorrect', 'total_duration', 'position', 'rank_position', 'league_number', 'updated_at']);
                }
                if ($isLive) {
                    $stale = $league->results();
                    if ($results->isNotEmpty()) {
                        $stale->whereNotIn('user_id', $results->pluck('user_id'));
                    }
                    $stale->delete();
                    if ($through !== null) {
                        $league->calculated_through_at = $through;
                        $league->calculated_at = CarbonImmutable::now();
                    }
                } else {
                    $league->calculated_at = CarbonImmutable::now();
                }
                if ($isLive) {
                    $league->saveQuietly();
                    if ($league->wasRecentlyCreated) {
                        League::insertLog($league, 'create');
                    }
                } else {
                    $league->save();
                }

                return $league;
            });
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$key]);
        }
        if ($league !== null) {
            TableCache::invalidate('leagues', 'league-results', 'options');
        }

        return $league;
    }

    private function results(LeagueRules $rules, LeaguePeriod $period, CarbonImmutable $through): Collection
    {
        $tableName = $rules->source;
        $baseTableName = $rules->baseTable();
        $foreignId = $rules->foreignId();
        $correctPoint = $rules->correctPoint;
        $incorrectPoint = $rules->incorrectPoint;

        return DB::query()
            ->fromSub(function ($query) use ($tableName, $baseTableName, $foreignId, $correctPoint, $incorrectPoint, $period, $through) {
                $query->from($tableName.' as trd')
                    ->join($baseTableName.' as tr', 'tr.id', '=', 'trd.'.$foreignId)
                    ->join('users as u', function ($join) {
                        $join->on('u.id', '=', 'tr.user_id')
                            ->where('u.status', 'active')
                            ->where('u.league_approval', 1)
                            ->whereNull('u.deleted_at');
                    })
                    ->when($baseTableName === 'tests_results', fn ($query) => $query->where('tr.completed', YesNoEnum::YES))
                    ->whereBetween('trd.updated_at', [
                        $period->start->format('Y-m-d H:i:s'),
                        $through->format('Y-m-d H:i:s'),
                    ])
                    ->groupBy('tr.user_id')
                    ->selectRaw(strtr(
                        'tr.user_id as user_id,
                        SUM(CASE WHEN trd.correct = 1 THEN :correct_point WHEN trd.correct = 0 THEN :incorrect_point ELSE 0 END) as total_score,
                        SUM(CASE WHEN trd.correct = 1 THEN 1 ELSE 0 END) as total_correct,
                        SUM(CASE WHEN trd.correct = 0 THEN 1 ELSE 0 END) as total_incorrect,
                        SUM(trd.time) as total_duration',
                        [
                            ':table_name' => $tableName,
                            ':foreign_id' => $foreignId,
                            ':column_correct' => 'IF(correct = 1, 1, 0)',
                            ':column_incorrect' => 'IF(correct = 0, 1, 0)',
                            ':correct_point' => $correctPoint,
                            ':incorrect_point' => $incorrectPoint,
                        ]
                    ));
            }, 't')
            ->selectRaw('
                t.*,
                RANK() OVER (
                    ORDER BY
                        t.total_score DESC,
                        t.total_correct DESC,
                        t.total_duration ASC
                ) as rank_position
            ')
            ->get();
    }
}
