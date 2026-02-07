<?php

namespace App\Jobs;

use App\Enums\YesNoEnum;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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

        $isCompleted = settings()->leagueResultCompleted ?? 1;
        $model = app(settings()->leagueResultModel ?? 'App\Models\ExamResult');

        // exam_results or tests_results
        if (!in_array($model->getTable(), ['exam_results', 'tests_results'])) {
            $this->fail('Lig tablosu tanımlı değil!');
            return;
        }
        // endif exam_results or tests_results

        $results = $model::query()
            ->selectRaw(strtr(
                'user_id,
                 SUM((correct_count * :correct_point) - (incorrect_count * :incorrect_point)) AS total_score,
                 SUM(correct_count) AS total_correct,
                 SUM(incorrect_count) AS total_incorrect,
                 SUM(:time) AS total_duration,
                 RANK() OVER (
                     ORDER BY
                         SUM((correct_count * :correct_point) - (incorrect_count * :incorrect_point)) DESC,
                         SUM(correct_count) DESC,
                         SUM(:time) ASC
                 ) AS rank_position',
                [
                    ':correct_point' => $correctPoint,
                    ':incorrect_point' => $incorrectPoint,
                    ':time' => $model->getTable() == 'exam_results' ? 'time' : 'duration',
                ]
            ))
            ->when($isCompleted, fn($query) => $query->where('completed', YesNoEnum::YES))
            ->whereIn('user_id', User::active()->leagueApproval()->select('id'))
            ->whereBetween('updated_at', [
                $this->started_at,
                $this->ended_at
            ])
            ->groupBy('user_id')
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
