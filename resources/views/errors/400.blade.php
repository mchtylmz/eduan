@extends('frontend.layouts.app')
@push('style')
    <style>
        .error-404__figure::after {
            content: '400';
        }
    </style>
@endpush
@section('content')
    @includeIf('errors.404')
@endsection
