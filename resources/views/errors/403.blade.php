@extends('frontend.layouts.app')
@section('content')
    <!-- Error 404 -->
    <div class="error-404 p-5 text-center">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <header class="error__header mb-3">
                    <h3 class="error__title">{{ __('Üzgünüz, bu sayfaya erişim yetkiniz bulunmuyor.!') }}</h3>
                    <p>{{ __('Yapay zeka soru yanıtlama için erişim izniniz bulunmuyor, yöneticiniz ile iletişime geçiniz!') }}</p>
                </header>
                <figure class="error-404__figure">
                    <img src="{{ asset('assets/img/403.webp') }}" alt="Error">
                </figure>
            </div>
        </div>
    </div>
    <!-- Error 404 / End -->
@endsection
