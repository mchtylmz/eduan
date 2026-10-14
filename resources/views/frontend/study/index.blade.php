@extends('frontend.layouts.app')
@section('content')
    <section class="member-page pt-30 pb-50">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <h1 class="member-page-title mb-0">{{ __('study.my_study') }}</h1>
                <a href="{{ route('frontend.profile') }}" class="btn btn-outline-secondary">{{ __('account.profile') }}</a>
            </div>
            <nav class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" aria-label="{{ __('study.week') }}">
                <a href="{{ route('frontend.study', ['week' => $bounds['start']->subWeek()->toDateString()]) }}" class="btn btn-outline-secondary">{{ __('study.previous_week') }}</a>
                <div class="text-center">
                    <strong>{{ $bounds['start']->format('d/m/Y') }} — {{ $bounds['end']->subDay()->format('d/m/Y') }}</strong>
                    <div class="small text-muted">{{ __('study.calendar_help') }}</div>
                </div>
                @if($bounds['end']->lessThanOrEqualTo($currentWeek))
                    <a href="{{ route('frontend.study', ['week' => $bounds['end']->toDateString()]) }}" class="btn btn-outline-secondary">{{ __('study.next_week') }}</a>
                @else
                    <span class="btn btn-outline-secondary disabled" aria-disabled="true">{{ __('study.next_week') }}</span>
                @endif
            </nav>
            @if($bounds['start']->lessThan($currentWeek))
                <a href="{{ route('frontend.study') }}" class="d-inline-block mb-3">{{ __('study.current_week') }}</a>
            @endif
            <livewire:frontend.study.study-report :week-start="$bounds['start']->toDateString()" />
            <livewire:frontend.study.study-form :week-start="$bounds['start']->toDateString()" />
        </div>
    </section>
@endsection
