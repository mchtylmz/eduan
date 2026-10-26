<?php

namespace App\Helpers;

use App\Actions\Study\AuthorizeStudyPanel;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StudyPanelQuery
{
    public static function students(): Builder
    {
        AuthorizeStudyPanel::run();
        $query = User::permission('user-type:user')->withoutPermission(['user-type:admin', 'user-type:teacher']);
        if (! auth()->user()->can('user-type:admin')) {
            $query->whereIn('users.id', auth()->user()->students()->select('users.id'));
        }

        return $query;
    }

    public static function summary(string $week): Builder
    {
        $bounds = StudyCalendar::bounds($week);
        $from = $bounds['start']->toDateString();
        $to = $bounds['end']->toDateString();
        $ids = self::students()->select('users.id')->toBase();
        $manual = StudyActivityEntry::whereIn('user_id', clone $ids)->where('activity_date', '>=', $from)->where('activity_date', '<', $to)
            ->selectRaw('user_id, SUM(questions) AS manual_questions, SUM(pages) AS pages, SUM(minutes) AS minutes, COUNT(*) AS fragments')->groupBy('user_id');
        $answers = StudyAutomaticWork::forOwners(clone $ids, false, $from.' 00:00:00', $to.' 00:00:00')
            ->unionAll(StudyAutomaticWork::forOwners(clone $ids, true, $from.' 00:00:00', $to.' 00:00:00'));
        $automatic = DB::query()->fromSub($answers, 'answers')->selectRaw('user_id, COUNT(*) AS questions')->groupBy('user_id');
        $targets = StudyWeeklyTarget::whereHas('week', fn ($q) => $q->where('starts_on', $from))
            ->select('user_id', 'questions', 'pages', 'minutes');
        $summary = self::students()->leftJoinSub($manual, 'manual', 'manual.user_id', '=', 'users.id')
            ->leftJoinSub($automatic, 'automatic', 'automatic.user_id', '=', 'users.id')
            ->leftJoinSub($targets, 'targets', 'targets.user_id', '=', 'users.id')
            ->select(['users.id', 'users.name', 'users.surname', 'users.email', 'users.username', 'users.status', 'users.deleted_at'])
            ->selectRaw('COALESCE(manual.manual_questions, 0) + COALESCE(automatic.questions, 0) AS questions, COALESCE(manual.pages, 0) AS pages, COALESCE(manual.minutes, 0) AS minutes, COALESCE(manual.fragments, 0) AS fragments')
            ->selectRaw('targets.questions AS target_questions, targets.pages AS target_pages, targets.minutes AS target_minutes');
        $rate = '(CASE WHEN target_questions > 0 THEN questions / target_questions * 100 ELSE 0 END + CASE WHEN target_pages > 0 THEN pages / target_pages * 100 ELSE 0 END + CASE WHEN target_minutes > 0 THEN minutes / target_minutes * 100 ELSE 0 END) / NULLIF((CASE WHEN target_questions > 0 THEN 1 ELSE 0 END + CASE WHEN target_pages > 0 THEN 1 ELSE 0 END + CASE WHEN target_minutes > 0 THEN 1 ELSE 0 END), 0)';
        $rates = DB::query()->fromSub($summary, 'amounts')->select('amounts.*')->selectRaw('ROUND('.$rate.', 1) AS achievement');

        return User::query()->fromSub($rates, 'users')->select('users.*')
            ->selectRaw("CASE WHEN achievement IS NULL THEN 'no_target' WHEN fragments = 0 AND questions = 0 THEN 'no_activity' WHEN questions + pages + minutes = 0 THEN 'zero_activity' WHEN achievement < 50 THEN 'low' ELSE 'on_track' END AS study_state");
    }
}
