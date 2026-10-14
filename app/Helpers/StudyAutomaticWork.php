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
        AuthorizeStudyAccess::run($user);
        if (! StudyTrackingSettings::enabled()) {
            return [];
        }
        $bounds = StudyCalendar::bounds($week);
        $from = $bounds['start']->format('Y-m-d H:i:s');
        $to = $bounds['end']->format('Y-m-d H:i:s');
        $tests = self::rows($user, false, $from, $to);
        $exams = self::rows($user, true, $from, $to);

        return DB::query()->fromSub($tests->unionAll($exams), 'study_answers')
            ->selectRaw('DATE(answered_at) AS activity_date')
            ->selectRaw('SUM(CASE WHEN source = ? THEN 1 ELSE 0 END) AS automatic_tests', ['test'])
            ->selectRaw('SUM(CASE WHEN source = ? THEN 1 ELSE 0 END) AS automatic_exams', ['exam'])
            ->groupByRaw('DATE(answered_at)')->orderBy('activity_date')->get()
            ->map(fn ($row) => ['activity_date' => $row->activity_date,
                'automatic_tests' => (int) $row->automatic_tests, 'automatic_exams' => (int) $row->automatic_exams])->all();
    }

    private static function rows(User $user, bool $timed, string $from, string $to): Builder
    {
        // Site "test" is Exam; site "exam" is TestsResult. Do not filter completed attempts.
        $details = $timed ? 'tests_result_details' : 'exam_result_details';
        $results = $timed ? 'tests_results' : 'exam_results';
        $foreignKey = $timed ? 'tests_result_id' : 'exam_result_id';
        $query = DB::table($details.' as d')->join($results.' as r', 'r.id', '=', 'd.'.$foreignKey)
            ->where('r.user_id', $user->id)->whereNull('r.deleted_at')->where('d.answer_id', '>', 0)->where('d.question_id', '>', 0)
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
