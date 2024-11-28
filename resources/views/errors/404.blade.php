@extends('frontend.layouts.app')
@section('content')
    <!-- Error 404 -->
    <div class="error-404 p-5 text-center">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <header class="error__header mb-3">
                    <h2 class="error__title">{{ __('OOOOPS! Sayfa Bulunamadı!') }}</h2>
                </header>
                <footer class="error__cta mb-3">
                    <a href="/" class="btn btn-outline-dark">{{ __("Anasayfa'ya Git") }}</a>
                </footer>
                <figure class="error-404__figure">
                    <img src="{{ asset('assets/img/404.png') }}" alt="Error">
                </figure>
            </div>
        </div>
    </div>
    <!-- Error 404 / End -->
@endsection
