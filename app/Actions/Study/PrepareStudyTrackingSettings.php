<?php

namespace App\Actions\Study;

use App\Helpers\StudyTrackingSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class PrepareStudyTrackingSettings
{
    use AsAction;

    public function handle(array $values): array
    {
        if (! StudyTrackingSettings::contains($values)) {
            return $values;
        }
        abort_unless(auth()->user()?->can('user-type:admin'), 403);
        foreach (array_keys($values) as $key) {
            if (str_starts_with($key, 'study') && ! in_array($key, StudyTrackingSettings::KEYS, true)) {
                throw ValidationException::withMessages(['settings.'.$key => __('study.invalid_setting')]);
            }
        }
        $study = array_intersect_key($values, array_flip(StudyTrackingSettings::KEYS));
        $study = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $study);
        Validator::make(['settings' => $study], [
            'settings.studyTrackingEnabled' => ['sometimes', 'required', Rule::in(['0', '1'])],
            'settings.studyTrackingStartedOn' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'settings.studyAllowPastActivities' => ['sometimes', 'required', Rule::in(['0', '1'])],
            'settings.studyMinimumTopicQuestions' => ['sometimes', 'required', 'integer', 'min:1', 'max:4294967295'],
        ], [], [
            'settings.studyTrackingEnabled' => __('study.enabled'),
            'settings.studyTrackingStartedOn' => __('study.started_on'),
            'settings.studyAllowPastActivities' => __('study.allow_past'),
            'settings.studyMinimumTopicQuestions' => __('study.minimum_questions'),
        ])->validate();

        // An empty form date must not reset an existing activation date.
        $date = $study['studyTrackingStartedOn'] ?? StudyTrackingSettings::startedOn();
        if (! $date) {
            $date = StudyTrackingSettings::startedOn();
        }
        $enabled = ($study['studyTrackingEnabled'] ?? (StudyTrackingSettings::enabled() ? '1' : '0')) === '1';
        if ($enabled && ! $date) {
            $date = now()->toDateString();
        }
        if (array_key_exists('studyTrackingStartedOn', $study) || $enabled) {
            $study['studyTrackingStartedOn'] = $date ?? '';
        }

        return array_replace($values, $study);
    }
}
