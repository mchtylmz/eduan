<?php

namespace App\Actions\Study;

use App\Helpers\StudyCalendar;
use App\Helpers\StudyTrackingSettings;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeStudyChange
{
    use AsAction;

    public function handle(User $user, string $kind, string $date): void
    {
        AuthorizeStudyAccess::run($user, write: true);
        abort_unless(in_array($kind, ['target', 'activity'], true), 422);
        abort_unless(StudyTrackingSettings::enabled() && StudyTrackingSettings::startedOn(), 403, __('study.disabled'));
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
        $day = StudyCalendar::day($date);
        $today = StudyCalendar::day(now());
        $week = StudyCalendar::bounds($day);
        $current = StudyCalendar::bounds($today);
        $past = $week['start']->lessThan($current['start']);
        $admin = auth()->user()->can('user-type:admin');

        if ($kind === 'target') {
            abort_if($week['start']->greaterThan($current['start']), 422, __('study.future_date'));
            // A midweek activation does not shorten the first target week.
            abort_if($week['end']->lessThanOrEqualTo(StudyCalendar::day(StudyTrackingSettings::startedOn())), 422, __('study.before_start'));
            abort_if($past && ! $admin, 403, __('study.closed_target'));
        } else {
            abort_if($day->greaterThan($today), 422, __('study.future_date'));
            abort_if($day->lessThan(StudyCalendar::day(StudyTrackingSettings::startedOn())), 422, __('study.before_start'));
            abort_if($past && ! $admin && ! StudyTrackingSettings::allowPastActivities(), 403, __('study.closed_activity'));
        }
    }
}
