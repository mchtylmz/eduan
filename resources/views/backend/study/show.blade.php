@extends('backend.layouts.app')
@push('style')<link rel="stylesheet" href="{{ asset('backend/assets/study-panel.css') }}?v={{ config('app.version') }}">@endpush
@push('script')<script src="{{ asset('backend/assets/study-panel.js') }}?v={{ config('app.version') }}"></script>@endpush
@section('content')
    @php
        $bounds = \App\Helpers\StudyCalendar::bounds($week);
        $currentWeek = \App\Helpers\StudyCalendar::bounds(now())['start'];
        $context = ['user' => $user->id, 'week' => $week, 'tab' => $tab] + request()->only(['lang', 'season']);
    @endphp
    <div class="study-panel" data-study-panel data-edit-label="{{ __('study.edit') }}" data-action-error="{{ __('study.panel_action_error') }}" x-data="{ studySection: 'evaluation' }">
        <div class="block block-rounded">
            <div class="block-header block-header-default flex-wrap gap-2">
                <div><h1 class="block-title">{{ $user->display_name }}</h1><div class="small text-muted text-break mt-1">{{ $user->email }}</div></div>
                <a href="{{ route('admin.study.index', ['week' => $week]) }}" class="btn btn-alt-secondary"><i class="fa fa-arrow-left me-1"></i>{{ __('study.student_list') }}</a>
            </div>
            <div class="block-content pb-3">
                <nav class="study-panel-week" aria-label="{{ __('study.week') }}">
                    <a class="btn btn-alt-secondary" href="{{ route('admin.study.show', array_replace($context, ['week' => $bounds['start']->subWeek()->toDateString()])) }}"><i class="fa fa-angle-left me-1"></i>{{ __('study.previous_week') }}</a>
                    <div><strong>{{ \App\Helpers\StudyCalendar::label($bounds['start'], 'j F') }} — {{ \App\Helpers\StudyCalendar::label($bounds['end']->subDay()) }}</strong><small>{{ __('study.week_simple') }}</small>@if($bounds['start']->lessThan($currentWeek))<a class="btn btn-sm btn-alt-secondary mt-2" href="{{ route('admin.study.show', array_replace($context, ['week' => $currentWeek->toDateString()])) }}"><i class="fa fa-calendar me-1"></i>{{ __('study.current_week') }}</a>@endif</div>
                    @if($bounds['end']->lessThanOrEqualTo($currentWeek))<a class="btn btn-alt-secondary" href="{{ route('admin.study.show', array_replace($context, ['week' => $bounds['end']->toDateString()])) }}">{{ __('study.next_week') }}<i class="fa fa-angle-right ms-1"></i></a>@else<span class="btn btn-alt-secondary disabled" aria-disabled="true">{{ __('study.next_week') }}</span>@endif
                </nav>
                <nav class="study-panel-tabs" aria-label="{{ __('study.sections') }}">
                    @foreach(['overview' => ['week_overview', 'fa-chart-pie'], 'history' => ['history_title_panel', 'fa-history'], 'activity' => ['entries', 'fa-pen'], 'analysis' => ['topic_analysis', 'fa-book'], 'notes' => ['notes', 'fa-comment']] as $key => [$label, $icon])
                        <a href="{{ route('admin.study.show', array_replace($context, ['tab' => $key])) }}" @class(['btn', 'btn-alt-primary' => $tab === $key, 'btn-alt-secondary' => $tab !== $key]) @if($tab === $key) aria-current="page" @endif data-study-panel-tab="{{ $key }}"><i class="fa {{ $icon }} me-1"></i>{{ __('study.'.$label) }}</a>
                    @endforeach
                </nav>
                @if(\App\Helpers\StudyTrackingSettings::enabled() && \App\Helpers\StudyTrackingSettings::startedOn())
                    @can('study:update')
                        <div class="study-panel-actions">
                            <button type="button" class="btn btn-alt-primary" data-study-panel-open="target"><i class="fa fa-bullseye me-1"></i>{{ __('study.edit_target_panel') }}</button>
                            <button type="button" class="btn btn-alt-primary" data-study-panel-open="activity"><i class="fa fa-plus me-1"></i>{{ __('study.add_activity') }}</button>
                            <button type="button" class="btn btn-primary" data-study-panel-open="note"><i class="fa fa-comment-medical me-1"></i>{{ __('study.add_note') }}</button>
                        </div>
                    @endcan
                @endif
            </div>
        </div>
        <div class="alert alert-danger d-none" role="alert" data-study-action-error></div>
        <livewire:backend.study.student-report :student-id="$user->id" :week-start="$week" :active-tab="$tab" />
        @can('study:update')
            <livewire:backend.study.study-editor :student-id="$user->id" :week-start="$week" />
            <livewire:backend.study.study-notes :student-id="$user->id" />
        @endcan
    </div>
@endsection
