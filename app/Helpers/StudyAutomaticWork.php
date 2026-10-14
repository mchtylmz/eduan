<?php

namespace App\Helpers;

use App\Actions\Study\AuthorizeStudyAccess;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class StudyAutomaticWork
{
    public static function daily(User $user, string $week): array
    {
        $bounds = StudyCalendar::bounds($week);

        return self::rangeDaily($user, $bounds['start']->toDateString(), $bounds['end']->toDateString());
    }

    public static function rangeDaily(User $user, string $fromDate, string $untilDate): array
    {
        AuthorizeStudyAccess::run($user);
        if (! StudyTrackingSettings::enabled()) {
            return [];
        }
        $from = StudyCalendar::day($fromDate)->format('Y-m-d H:i:s');
        $to = StudyCalendar::day($untilDate)->format('Y-m-d H:i:s');
        $tests = self::forOwners([$user->id], false, $from, $to);
        $exams = self::forOwners([$user->id], true, $from, $to);

        return DB::query()->fromSub($tests->unionAll($exams), 'study_answers')
            ->selectRaw('DATE(answered_at) AS activity_date')
            ->selectRaw('SUM(CASE WHEN source = ? THEN 1 ELSE 0 END) AS automatic_tests', ['test'])
            ->selectRaw('SUM(CASE WHEN source = ? THEN 1 ELSE 0 END) AS automatic_exams', ['exam'])
            ->groupByRaw('DATE(answered_at)')->orderBy('activity_date')->get()
            ->map(fn ($row) => ['activity_date' => $row->activity_date,
                'automatic_tests' => (int) $row->automatic_tests, 'automatic_exams' => (int) $row->automatic_exams])->all();
    }

    /** Internal query factory; callers authorize the report owner before executing. */
    public static function forOwners(array $ownerIds, bool $timed, string $from, string $to, bool $accuracy = false): Builder
    {
        // Site "test" is Exam; site "exam" is TestsResult. Do not filter completed attempts.
        $details = $timed ? 'tests_result_details' : 'exam_result_details';
        $results = $timed ? 'tests_results' : 'exam_results';
        $foreignKey = $timed ? 'tests_result_id' : 'exam_result_id';
        $query = DB::table($details.' as d')->join($results.' as r', 'r.id', '=', 'd.'.$foreignKey)
            ->whereIn('r.user_id', $ownerIds)->whereNull('r.deleted_at')->where('d.answer_id', '>', 0)->where('d.question_id', '>', 0)
            ->addSelect(['r.user_id', 'd.topic_id', 'd.lesson_id'])
            ->selectRaw('COALESCE(d.study_answered_at, d.created_at) AS answered_at')
            ->selectRaw('? AS source', [$timed ? 'exam' : 'test'])
            ->where(function (Builder $query) use ($from, $to) {
                $query->where(function (Builder $query) use ($from, $to) {
                    $query->whereNotNull('d.study_answered_at')->where('d.study_answered_at', '>=', $from)->where('d.study_answered_at', '<', $to);
                })->orWhere(function (Builder $query) use ($from, $to) {
                    // Approved historical fallback; grading's updated_at is never a work date.
                    $query->whereNull('d.study_answered_at')->where('d.created_at', '>=', $from)->where('d.created_at', '<', $to);
                });
            });
        if ($accuracy) {
            $query->leftJoin('answers as a', fn ($join) => $join->on('a.id', '=', 'd.answer_id')->on('a.question_id', '=', 'd.question_id'));
            $query->selectRaw($timed
                ? 'CASE WHEN r.completed = 0 THEN a.correct WHEN d.correct IN (0, 1) THEN d.correct ELSE a.correct END AS accuracy'
                : 'CASE WHEN d.correct IN (0, 1) THEN d.correct ELSE a.correct END AS accuracy');
        }
        if (! $timed) {
            // Historical untimed rows have no unique constraint. Count the latest existing slot once.
            $query->whereNotExists(function (Builder $duplicates) {
                $duplicates->selectRaw('1')->from('exam_result_details as newer')
                    ->whereColumn('newer.exam_result_id', 'd.exam_result_id')->whereColumn('newer.question_id', 'd.question_id')
                    ->whereColumn('newer.id', '>', 'd.id');
            });
        }

        return $query;
    }
}
