<?php

namespace App\Helpers;

use App\Actions\Study\AuthorizeStudyPanel;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudyPanelQuery
{
    public static function sorts(): array
    {
        return ['name' => 'name', 'surname' => 'surname', 'questions' => 'questions', 'pages' => 'pages', 'minutes' => 'minutes', 'achievement' => 'achievement'];
    }

    public static function parameters(Request $request): array
    {
        $parameters = ServerTable::parameters($request, self::sorts(), 'name', [
            'week' => 'nullable|date_format:Y-m-d',
            'state' => ['nullable', Rule::in(array_keys(self::states()))],
            'teacher_id' => ['nullable', 'integer', 'min:1', Rule::prohibitedIf(! auth()->user()?->can('user-type:admin'))],
        ]);
        if (! empty($parameters['teacher_id'])) {
            abort_unless(self::teachers()->whereKey($parameters['teacher_id'])->exists(), 422);
            $parameters['teacher_id'] = (int) $parameters['teacher_id'];
        }

        return $parameters;
    }

    public static function states(): array
    {
        return [
            'no_target' => __('study.state_no_target'),
            'has_target' => __('study.filter_has_target'),
            'target_questions' => __('study.filter_target_questions'),
            'target_pages' => __('study.filter_target_pages'),
            'target_minutes' => __('study.filter_target_minutes'),
            'no_activity' => __('study.state_no_activity'),
            'on_track' => __('study.state_on_track'),
            'zero_activity' => __('study.state_zero_activity'),
            'below_25' => __('study.filter_below_25'),
            'low' => __('study.state_low'),
            'below_75' => __('study.filter_below_75'),
            'above_50' => __('study.filter_above_50'),
            'above_70' => __('study.filter_above_70'),
            'above_100' => __('study.filter_above_100'),
        ];
    }

    public static function teachers(): Builder
    {
        return User::permission('user-type:teacher');
    }

    public static function filtered(string $week, array $p): Builder
    {
        $query = self::summary($week);
        if ($p['search'] !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$p['search'].'%')->orWhere('surname', 'like', '%'.$p['search'].'%')->orWhere('email', 'like', '%'.$p['search'].'%')->orWhere('username', 'like', '%'.$p['search'].'%'));
        }
        if (! empty($p['status'])) {
            $query->where('status', $p['status']);
        }
        if (! empty($p['teacher_id'])) {
            abort_unless(auth()->user()->can('user-type:admin') && self::teachers()->whereKey($p['teacher_id'])->exists(), 403);
            $query->whereIn('id', DB::table('teacher_student')->where('teacher_id', $p['teacher_id'])->select('user_id'));
        }
        match ($p['state'] ?? '') {
            'no_target' => $query->whereNull('achievement'),
            'has_target' => $query->where(fn ($q) => $q->where('target_questions', '>', 0)->orWhere('target_pages', '>', 0)->orWhere('target_minutes', '>', 0)),
            'target_questions' => $query->where('target_questions', '>', 0),
            'target_pages' => $query->where('target_pages', '>', 0),
            'target_minutes' => $query->where('target_minutes', '>', 0),
            'no_activity' => $query->where('fragments', 0)->where('questions', 0),
            'zero_activity' => $query->where('fragments', '>', 0)->whereRaw('questions + pages + minutes = 0'),
            'low' => $query->where('achievement', '<', 50),
            'below_25' => $query->where('achievement', '<', 25),
            'below_75' => $query->where('achievement', '<', 75),
            'above_50' => $query->where('achievement', '>', 50),
            'above_70' => $query->where('achievement', '>', 70),
            'above_100' => $query->where('achievement', '>', 100),
            'on_track' => $query->where('achievement', '>=', 50)->whereRaw('questions + pages + minutes > 0'),
            default => null,
        };

        return $query->orderBy(self::sorts()[$p['sort']], $p['order'])->orderBy('id');
    }

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
