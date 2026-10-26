@extends('backend.layouts.app')
@push('style')<link rel="stylesheet" href="{{ asset('backend/assets/study-panel.css') }}?v={{ config('app.version') }}">@endpush
@section('content')
    <livewire:backend.study.study-communication :student-id="$student?->id" :week-start="$week" :filters="$filters" :initial-channel="$channel" />
@endsection
