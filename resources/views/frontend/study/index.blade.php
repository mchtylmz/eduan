@extends('frontend.layouts.app')
@section('content')
    @php
        $isCurrentWeek = $bounds['start']->equalTo($currentWeek);
        $previousStart = $bounds['start']->subWeek();
        $nextStart = $bounds['end'];
    @endphp
    <section class="study-page pt-30 pb-50" x-data="{ studySection: 'week' }">
        <div class="container">
            <header class="study-page-heading">
                <div><h1 class="member-page-title mb-1">{{ __('study.my_study') }}</h1><p class="mb-0 text-muted">{{ __('study.student_intro') }}</p></div>
                <a href="{{ route('frontend.profile') }}" class="study-outline-button"><i class="fa fa-arrow-left" aria-hidden="true"></i> {{ __('study.back_account') }}</a>
            </header>
            <nav class="study-week-picker" aria-label="{{ __('study.week') }}">
                <a class="study-week-link study-week-previous study-outline-button" href="{{ route('frontend.study', ['week' => $previousStart->toDateString()]) }}">
                    <i class="fa fa-angle-left" aria-hidden="true"></i><span>{{ __('study.previous_week') }}<small>{{ \App\Helpers\StudyCalendar::label($previousStart, 'j F') }} — {{ \App\Helpers\StudyCalendar::label($previousStart->addDays(6), 'j F Y') }}</small></span>
                </a>
                <div class="study-selected-week">
                    <span class="study-week-label">{{ __($isCurrentWeek ? 'study.this_week' : 'study.selected_week') }}</span>
                    <h2>{{ \App\Helpers\StudyCalendar::label($bounds['start'], 'j F') }} — {{ \App\Helpers\StudyCalendar::label($bounds['end']->subDay(), 'j F Y') }}</h2>
                    <p>{{ __('study.week_simple') }}</p>
                    @unless($isCurrentWeek)<a class="study-outline-button study-current-week" href="{{ route('frontend.study') }}"><i class="fa fa-calendar-check" aria-hidden="true"></i> {{ __('study.current_week') }}</a>@endunless
                </div>
                @if($nextStart->lessThanOrEqualTo($currentWeek))
                    <a class="study-week-link study-week-next study-outline-button" href="{{ route('frontend.study', ['week' => $nextStart->toDateString()]) }}">
                        <span>{{ __($nextStart->equalTo($currentWeek) ? 'study.current_week' : 'study.next_week') }}<small>{{ \App\Helpers\StudyCalendar::label($nextStart, 'j F') }} — {{ \App\Helpers\StudyCalendar::label($nextStart->addDays(6), 'j F Y') }}</small></span><i class="fa fa-angle-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="study-week-link study-week-next study-outline-button is-disabled" aria-disabled="true"><span>{{ __('study.next_week') }}<small>{{ __('study.future_week_help') }}</small></span><i class="fa fa-angle-right" aria-hidden="true"></i></span>
                @endif
            </nav>
            <nav class="study-section-nav" aria-label="{{ __('study.sections') }}">
                @foreach(['week' => ['week_overview', 'fa-chart-bar'], 'history' => ['my_history', 'fa-history'], 'analysis' => ['topic_analysis', 'fa-book-open'], 'notes' => ['notes', 'fa-comment']] as $section => [$label, $icon])
                    <button type="button" @click="studySection = '{{ $section }}'" :class="{ 'is-active': studySection === '{{ $section }}' }" :aria-pressed="(studySection === '{{ $section }}').toString()" aria-controls="study-{{ $section }}-content"><i class="fa {{ $icon }}" aria-hidden="true"></i><span>{{ __('study.'.$label) }}</span></button>
                @endforeach
            </nav>
            <div class="study-quick-actions">
                <button type="button" class="study-outline-button" data-study-open="target" @click="$dispatch('study-open-editor', { kind: 'target' })"><i class="fa fa-bullseye" aria-hidden="true"></i>{{ __('study.weekly_target') }}</button>
                <button type="button" class="study-button" data-study-open="activity" @click="$dispatch('study-open-editor', { kind: 'activity' })"><i class="fa fa-plus" aria-hidden="true"></i>{{ __('study.add_activity') }}</button>
            </div>
            <livewire:frontend.study.study-report :week-start="$bounds['start']->toDateString()" />
            <livewire:frontend.study.study-form :week-start="$bounds['start']->toDateString()" />
            <div id="study-notes-content" x-show="studySection === 'notes'" x-cloak>
                <livewire:frontend.study.visible-study-notes />
            </div>
        </div>
    </section>
@endsection

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/js/flatpickr/flatpickr.min.css') }}">
@endpush

@push('script')
    <script defer src="{{ asset('assets/study-workspace.js') }}?v={{ config('app.version') }}"></script>
@endpush
