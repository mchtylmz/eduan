@php
    $studyEnabled = \App\Helpers\StudyTrackingSettings::enabled();
    $studyStartedOn = \App\Helpers\StudyTrackingSettings::startedOn();
@endphp
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST">
    @csrf
    <div class="alert alert-info">
        {{ __('study.calendar_help') }}
        <span class="d-block mt-1">{{ __('study.timezone') }}: {{ config('app.timezone') }}</span>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="studyTrackingEnabled">{{ __('study.enabled') }}</label>
            <select class="form-control" id="studyTrackingEnabled" name="settings[studyTrackingEnabled]" required>
                <option value="0" @selected(! $studyEnabled)>{{ __('study.off') }}</option>
                <option value="1" @selected($studyEnabled)>{{ __('study.on') }}</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="studyTrackingStartedOn">{{ __('study.started_on') }}</label>
            <input type="date" class="form-control" id="studyTrackingStartedOn" name="settings[studyTrackingStartedOn]"
                   max="{{ now()->toDateString() }}" value="{{ $studyStartedOn ?? '' }}">
            <div class="form-text">{{ __('study.start_help') }}</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="studyAllowPastActivities">{{ __('study.allow_past') }}</label>
            <select class="form-control" id="studyAllowPastActivities" name="settings[studyAllowPastActivities]" required>
                <option value="0" @selected(! \App\Helpers\StudyTrackingSettings::allowPastActivities())>{{ __('study.off') }}</option>
                <option value="1" @selected(\App\Helpers\StudyTrackingSettings::allowPastActivities())>{{ __('study.on') }}</option>
            </select>
            <div class="form-text">{{ __('study.past_help') }}</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="studyMinimumTopicQuestions">{{ __('study.minimum_questions') }}</label>
            <input type="number" min="1" max="4294967295" class="form-control" id="studyMinimumTopicQuestions"
                   name="settings[studyMinimumTopicQuestions]" value="{{ \App\Helpers\StudyTrackingSettings::minimumTopicQuestions() }}" required>
            <div class="form-text">{{ __('study.minimum_help') }}</div>
        </div>
    </div>
    @can('settings:update')
        <div class="mb-3 text-center py-2 mt-3">
            <button type="submit" class="btn btn-alt-primary px-4"><i class="fa fa-save mx-2"></i>{{ __('study.save') }}</button>
        </div>
    @endcan
</form>
