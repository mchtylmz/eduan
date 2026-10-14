@extends('backend.layouts.app')
@section('content')
    @php($bounds = \App\Helpers\StudyCalendar::bounds($week))
    <div class="block block-rounded">
        <div class="block-header block-header-default flex-wrap gap-2">
            <h1 class="block-title">{{ $title }}</h1>
            <a href="{{ route('admin.study.index', ['week' => $week]) }}" class="btn btn-alt-secondary">{{ __('study.student_list') }}</a>
        </div>
        <div class="block-content pb-4">
            <nav class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" aria-label="{{ __('study.week') }}">
                <a class="btn btn-alt-secondary" href="{{ route('admin.study.show', ['user' => $user->id, 'week' => $bounds['start']->subWeek()->toDateString()]) }}">{{ __('study.previous_week') }}</a>
                <strong>{{ $bounds['start']->format('d/m/Y') }} — {{ $bounds['end']->subDay()->format('d/m/Y') }}</strong>
                @if($bounds['end']->lessThanOrEqualTo(\App\Helpers\StudyCalendar::bounds(now())['start']))
                    <a class="btn btn-alt-secondary" href="{{ route('admin.study.show', ['user' => $user->id, 'week' => $bounds['end']->toDateString()]) }}">{{ __('study.next_week') }}</a>
                @endif
            </nav>
            <livewire:backend.study.student-report :student-id="$user->id" :week-start="$week" />
            @can('study:update')<livewire:backend.study.study-editor :student-id="$user->id" :week-start="$week" />@endcan
            <livewire:backend.study.study-notes :student-id="$user->id" />
        </div>
    </div>
@endsection
