<?php

namespace App\Helpers;

use App\Actions\Study\AuthorizeStudyAccess;
use App\Enums\RoleTypeEnum;
use App\Models\Season;
use App\Models\StudyActivityEntry;
use App\Models\StudyWeeklyTarget;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudyAnalytics
{
    public static function report(User $user, string $week, ?Season $season): array
    {
        AuthorizeStudyAccess::run($user);
        $selected = StudyCalendar::bounds($week);
        $trendWeeks = StudyCalendar::weeksBetween($selected['start']->subWeeks(3), $selected['start']);
        $yearWeeks = $season ? StudyCalendar::weeksBetween($season->start_date, $season->end_date) : [];
        $all = [...$yearWeeks, ...$trendWeeks];
        $from = min(array_map(fn ($row) => $row['start']->toDateString(), $all));
        $to = max(array_map(fn ($row) => $row['end']->toDateString(), $all));
        $manual = StudyActivityEntry::query()->where('user_id', $user->id)->where('activity_date', '>=', $from)->where('activity_date', '<', $to)
            ->selectRaw('activity_date, SUM(questions) AS manual_questions, SUM(pages) AS pages, SUM(minutes) AS minutes, COUNT(*) AS fragments')
            ->groupBy('activity_date')->get()->map(fn ($day) => ['activity_date' => $day->activity_date->toDateString(),
                'manual_questions' => (int) $day->manual_questions, 'pages' => (int) $day->pages, 'minutes' => (int) $day->minutes, 'fragments' => (int) $day->fragments])->all();
        $daily = StudyDailyTotals::merge($manual, StudyAutomaticWork::rangeDaily($user, $from, $to));
        $actuals = [];
        foreach ($daily as $day) {
            $start = StudyCalendar::bounds($day['activity_date'])['start']->toDateString();
            $actuals[$start] ??= ['questions' => 0, 'pages' => 0, 'minutes' => 0, 'fragments' => 0];
            foreach (array_keys($actuals[$start]) as $field) {
                $actuals[$start][$field] += $day[$field];
            }
        }
        $targets = StudyWeeklyTarget::where('user_id', $user->id)->whereHas('week', fn ($query) => $query->where('starts_on', '>=', $from)->where('starts_on', '<', $to))
            ->with('week:id,starts_on')->get()->keyBy(fn ($target) => $target->week->starts_on->toDateString());
        $assemble = function ($bounds) use ($actuals, $targets) {
            $start = $bounds['start']->toDateString();
            $actual = $actuals[$start] ?? ['questions' => 0, 'pages' => 0, 'minutes' => 0, 'fragments' => 0];
            $target = $targets->get($start)?->only(['questions', 'pages', 'minutes']) ?? [];
            $rates = StudyMetrics::achievement($target, $actual);

            return ['start' => $start, 'end' => $bounds['end']->subDay()->toDateString(), 'target' => $target,
                'actual' => $actual, 'rates' => $rates, 'state' => $bounds['start']->greaterThan(StudyCalendar::day(now())) ? 'future' : ($rates['overall'] === null ? 'no_target'
                    : ($actual['fragments'] === 0 && $actual['questions'] === 0 ? 'no_activity' : ($actual['questions'] + $actual['pages'] + $actual['minutes'] === 0 ? 'zero_activity' : ($rates['overall'] < 50 ? 'low' : 'on_track'))))];
        };
        $current = $assemble($selected);
        $topics = self::topics($user, $selected);
        $comparisons = self::comparisons($user, $selected, $current);

        return ['current' => $current, 'trend' => array_map($assemble, $trendWeeks), 'history' => array_map($assemble, $yearWeeks),
            'topics' => $topics, 'comparisons' => $comparisons];
    }

    private static function answers(array $owners, array $bounds)
    {
        $from = $bounds['start']->format('Y-m-d H:i:s');
        $to = $bounds['end']->format('Y-m-d H:i:s');

        return DB::query()->fromSub(StudyAutomaticWork::forOwners($owners, false, $from, $to, true)
            ->unionAll(StudyAutomaticWork::forOwners($owners, true, $from, $to, true)), 'work');
    }

    private static function topics(User $user, array $bounds): array
    {
        $rows = self::answers([$user->id], $bounds)->whereIn('accuracy', [0, 1])->select('topic_id', 'lesson_id')
            ->selectRaw('SUM(CASE WHEN accuracy = 1 THEN 1 ELSE 0 END) AS correct, SUM(CASE WHEN accuracy = 0 THEN 1 ELSE 0 END) AS incorrect')
            ->groupBy('topic_id', 'lesson_id')->get();
        $names = Topic::whereIn('id', $rows->pluck('topic_id'))->with('lesson:id,name')->get()->keyBy('id');

        return $rows->map(fn ($row) => ['name' => $names->get($row->topic_id)?->title ?: __('study.unknown_topic'),
            'lesson' => $names->get($row->topic_id)?->lesson?->name ?? '']
            + StudyMetrics::topic((int) $row->correct, (int) $row->incorrect, StudyTrackingSettings::minimumTopicQuestions()))->all();
    }

    private static function comparisons(User $user, array $bounds, array $current): array
    {
        if (! $user->can(RoleTypeEnum::USER->value) || $user->canany([RoleTypeEnum::ADMIN->value, RoleTypeEnum::TEACHER->value]) || $current['rates']['overall'] === null) {
            return [];
        }
        $targets = StudyWeeklyTarget::whereHas('user', fn ($query) => $query->active()->permission(RoleTypeEnum::USER->value)->withoutPermission(RoleTypeEnum::ADMIN->value)->withoutPermission(RoleTypeEnum::TEACHER->value))
            ->whereHas('week', fn ($query) => $query->where('starts_on', $bounds['start']->toDateString()))
            ->where(fn ($query) => $query->where('questions', '>', 0)->orWhere('pages', '>', 0)->orWhere('minutes', '>', 0))->get()->keyBy('user_id');
        if (! $targets->has($user->id)) {
            return [];
        }
        $ids = $targets->keys()->all();
        $manual = StudyActivityEntry::whereIn('user_id', $ids)->where('activity_date', '>=', $bounds['start']->toDateString())->where('activity_date', '<', $bounds['end']->toDateString())
            ->select('user_id')->selectRaw('SUM(questions) AS questions, SUM(pages) AS pages, SUM(minutes) AS minutes')->groupBy('user_id')->get()->keyBy('user_id');
        $automatic = self::answers($ids, $bounds)->select('user_id')->selectRaw('COUNT(*) AS questions, SUM(CASE WHEN accuracy = 1 THEN 1 ELSE 0 END) AS correct, SUM(CASE WHEN accuracy = 0 THEN 1 ELSE 0 END) AS incorrect')->groupBy('user_id')->get()->keyBy('user_id');
        $metrics = [];
        foreach ($targets as $id => $target) {
            $auto = $automatic->get($id);
            $actual = ['questions' => (int) ($manual->get($id)?->questions ?? 0) + (int) ($auto?->questions ?? 0),
                'pages' => (int) ($manual->get($id)?->pages ?? 0), 'minutes' => (int) ($manual->get($id)?->minutes ?? 0)];
            $known = (int) ($auto?->correct ?? 0) + (int) ($auto?->incorrect ?? 0);
            $metrics[$id] = $actual + ['achievement' => StudyMetrics::achievement($target->only(['questions', 'pages', 'minutes']), $actual)['overall'],
                'accuracy' => $known ? round(100 * $auto->correct / $known, 1) : null];
        }
        $teachers = $user->teachers()->pluck('users.id');
        $groupIds = DB::table('teacher_student')->whereIn('teacher_id', $teachers)->distinct()->pluck('user_id')->all();
        $groups = ['all_students' => $ids, 'teacher_group' => array_values(array_intersect($ids, $groupIds))];
        $result = [];
        foreach ($groups as $key => $members) {
            foreach (['questions', 'pages', 'minutes', 'achievement', 'accuracy'] as $field) {
                $score = $metrics[$user->id][$field];
                $result[$key][$field] = $score === null || ! $members ? null : StudyMetrics::percentile((float) $score, array_map(fn ($id) => $metrics[$id][$field], $members));
            }
        }

        return $result;
    }
}
