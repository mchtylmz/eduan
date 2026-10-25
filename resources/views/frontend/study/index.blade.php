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
                <a href="{{ route('frontend.profile') }}" class="study-account-link"><i class="fa fa-arrow-left" aria-hidden="true"></i> {{ __('study.back_account') }}</a>
            </header>
            <nav class="study-week-picker" aria-label="{{ __('study.week') }}">
                <a class="study-week-link study-week-previous" href="{{ route('frontend.study', ['week' => $previousStart->toDateString()]) }}">
                    <i class="fa fa-angle-left" aria-hidden="true"></i><span>{{ __('study.previous_week') }}<small>{{ dateFormat($previousStart, 'j F') }} — {{ dateFormat($previousStart->addDays(6), 'j F Y') }}</small></span>
                </a>
                <div class="study-selected-week">
                    <span class="study-week-label">{{ __($isCurrentWeek ? 'study.this_week' : 'study.selected_week') }}</span>
                    <h2>{{ dateFormat($bounds['start'], 'j F') }} — {{ dateFormat($bounds['end']->subDay(), 'j F Y') }}</h2>
                    <p>{{ __('study.week_simple') }}</p>
                    @unless($isCurrentWeek)<a href="{{ route('frontend.study') }}">{{ __('study.current_week') }}</a>@endunless
                </div>
                @if($nextStart->lessThanOrEqualTo($currentWeek))
                    <a class="study-week-link study-week-next" href="{{ route('frontend.study', ['week' => $nextStart->toDateString()]) }}">
                        <span>{{ __($nextStart->equalTo($currentWeek) ? 'study.current_week' : 'study.next_week') }}<small>{{ dateFormat($nextStart, 'j F') }} — {{ dateFormat($nextStart->addDays(6), 'j F Y') }}</small></span><i class="fa fa-angle-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="study-week-link study-week-next is-disabled" aria-disabled="true"><span>{{ __('study.next_week') }}<small>{{ __('study.future_week_help') }}</small></span><i class="fa fa-angle-right" aria-hidden="true"></i></span>
                @endif
            </nav>
            <nav class="study-section-nav" aria-label="{{ __('study.sections') }}">
                @foreach(['week' => 'week_overview', 'history' => 'my_history', 'analysis' => 'topic_analysis', 'notes' => 'notes'] as $section => $label)
                    <button type="button" @click="studySection = '{{ $section }}'" :class="{ 'is-active': studySection === '{{ $section }}' }" :aria-pressed="(studySection === '{{ $section }}').toString()" aria-controls="study-{{ $section }}-content">{{ __('study.'.$label) }}</button>
                @endforeach
            </nav>
            <div class="study-quick-actions">
                <button type="button" class="study-quick-link" @click="studySection = 'week'; $nextTick(() => document.getElementById('study-target-form')?.scrollIntoView({behavior: 'smooth', block: 'start'}))"><i class="fa fa-bullseye" aria-hidden="true"></i>{{ __('study.weekly_target') }}</button>
                <button type="button" class="study-button" @click="studySection = 'week'; $nextTick(() => document.getElementById('study-activity-form')?.scrollIntoView({behavior: 'smooth', block: 'start'}))"><i class="fa fa-plus" aria-hidden="true"></i>{{ __('study.add_activity') }}</button>
            </div>
            <livewire:frontend.study.study-report :week-start="$bounds['start']->toDateString()" />
            <div id="study-week-content" x-show="studySection === 'week'">
                <livewire:frontend.study.study-form :week-start="$bounds['start']->toDateString()" />
            </div>
            <div id="study-notes-content" x-show="studySection === 'notes'" x-cloak>
                <livewire:frontend.study.visible-study-notes />
            </div>
        </div>
    </section>
@endsection
