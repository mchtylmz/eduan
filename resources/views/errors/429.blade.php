@extends('frontend.layouts.app')
@php($title = __('errors.too_many_title'))
@section('content')
    <section class="container py-5 text-center" aria-labelledby="rate-limit-title">
        <p class="text-muted mb-2">429</p>
        <h1 class="h2 mb-3" id="rate-limit-title">{{ __('errors.too_many_title') }}</h1>
        <p class="mb-4">{{ __('errors.too_many_description') }}</p>
        <a class="btn btn-outline-primary" href="{{ url('/') }}">{{ __('errors.home') }}</a>
    </section>
@endsection
