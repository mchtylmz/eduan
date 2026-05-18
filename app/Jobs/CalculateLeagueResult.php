<?php

namespace App\Jobs;

use App\Enums\YesNoEnum;
use App\Models\ExamResultDetail;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CalculateLeagueResult implements ShouldQueue
{
    use Queueable;

    public string $started_at;
    public string $ended_at;

    /**
     * Create a new job instance.
     */
    public function __construct(string $started_at, string $ended_at)
    {
        $this->started_at = Carbon::parse($started_at)->format('Y-m-d H:i:00');
        $this->ended_at = Carbon::parse($ended_at)->format('Y-m-d H:i:59');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (settings()->leagueStatus == 'passive') {
            $this->fail('lig hesaplama kapalı');
            return;
        }

        $maxPositionNumber = intval(settings()->leagueUserCount ?? 10);
        $correctPoint = intval(settings()->leagueCorrectPoint ?? 3);
        $incorrectPoint = intval(settings()->leagueIncorrectPoint ?? -1) * -1;

        //$isCompleted = settings()->leagueResultCompleted ?? 1;
        $model = app(settings()->leagueResultModel ?? 'App\Models\ExamResultDetail');

        // exam_results or tests_results
        if (!in_array($model->getTable(), ['exam_result_details', 'tests_result_details'])) {
            $this->fail('Lig tablosu tanımlı değil!');
            return;
        }
        // endif exam_result_details or tests_result_details

        $foreignId = match ($model->getTable()) {
            'exam_result_details' => 'exam_result_id',
            'tests_result_details' => 'tests_result_id',
            default => false
        };
        if (!$foreignId) {
            $this->fail('Lig hesaplama $foreignId hatası!');
            return;
        }

        $baseTableName = match ($model->getTable()) {
            'exam_result_details' => 'exam_results',
            'tests_result_details' => 'tests_results',
            default => false
        };
        if (!$baseTableName) {
            $this->fail('Lig hesaplama $baseTableName hatası!');
            return;
        }

        $tableName = $model->getTable();

        $results = DB::query()
            ->fromSub(function ($query) use($tableName, $baseTableName, $foreignId, $correctPoint, $incorrectPoint) {
                $query->from($tableName . ' as trd')
                    ->join($baseTableName . ' as tr', 'tr.id', '=', 'trd.' . $foreignId)
                    ->join('users as u', function ($join) {
                        $join->on('u.id', '=', 'tr.user_id')
                            ->where('u.status', 'active')
                            ->where('u.league_approval', 1)
                            ->whereNull('u.deleted_at');
                    })
                    ->whereBetween('trd.updated_at', [
                        $this->started_at,
                        $this->ended_at
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

        if (empty($results)) {
            $this->fail('Lig hesaplama aralığında data yok! - ' . $this->started_at . '-' . $this->ended_at);
            return;
        }

        $league = League::updateOrCreate(
            // where
            [
                'label' => $model->getTable(),
                'start_at' => $this->started_at,
                'end_at' => $this->ended_at
            ],

            // update or insert
            [
                'code' => 'hyp',
                'label' => $model->getTable(),
                'calculated_at' => now()->format('Y-m-d H:i:s'),
                'start_at' => $this->started_at,
                'end_at' => $this->ended_at,
            ]
        );

        $league->update(['code' => $league->code . $league->id]);

        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'league_id' => $league->id,
                'user_id' => $result->user_id,
                'total_score' => $result->total_score,
                'total_correct' => $result->total_correct,
                'total_incorrect' => $result->total_incorrect,
                'total_duration' => $result->total_duration,
                'rank_position' => $result->rank_position,
                'position' => (($result->rank_position - 1) % $maxPositionNumber) + 1,
                'league_number' => ceil($result->rank_position / $maxPositionNumber),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        if (empty($data)) {
            $this->fail('Lig detay datası boş!');
            return;
        }

        LeagueResult::upsert(
            $data,
            ['league_id', 'user_id'],
            ['total_score', 'total_correct', 'total_incorrect', 'total_duration', 'position', 'rank_position']
        );
    }
}
