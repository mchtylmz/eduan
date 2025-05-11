<!Doctype html>
<html class="no-js" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="device" content="{{ agentDevice() }}">
    <meta name="agent" content="{{ request()->userAgent() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="locale" content="{{ app()->getLocale() }}">
    <meta name="language" content="{{ app()->getLocale() }}">
    <meta name="app-version" content="{{ config('app.version') }}">
    <meta name="theme-color" content="{{ settings()->primaryColor ?? '#000' }}" />
    <meta name="apple-mobile-web-app-status-bar-style" content="{{ settings()->primaryColor ?? '#000' }}">
    <meta name="ip-address" content="{{ request()->ip() }}" />
    <meta name="canonical" content="{{ request()->fullUrl() }}" />
    <meta name="robots" content="index, follow" />
    <meta name="apple-mobile-web-app-title" content="{{ settingLocale('siteTitle') }}">
    <link rel="apple-touch-startup-image" href="{{ asset(settings()->siteLogo) }}">

    <!-- default stack for seo -->
    @if($seoContent = trim(\Illuminate\Support\Facades\View::yieldPushContent('seo')))
        {!! $seoContent !!}
    @else
        <x-seo.tags />
    @endif
    <!-- default stack for seo -->

    <title>{{ !empty($title) ? $title . ' | ' : '' }}{{ settingLocale('siteTitle') }}</title>

    @if($siteFavicon = settings()->siteFavicon)
        <link rel="shortcut icon" href="{{ asset($siteFavicon) }}">
        <link rel="apple-touch-icon" href="{{ asset($siteFavicon) }}">
    @foreach([192, 256, 384, 512, 1024, 2048] as $width)
        <link rel="icon" type="image/png" sizes="{{ $width.'x'.$width }}" href="{{ asset('icons/fav-'.$width.'.jpg') }}?v={{ time() }}">
        <link rel="apple-touch-icon" sizes="{{ $width.'x'.$width }}" href="{{ asset('icons/fav-'.$width.'.jpg') }}?v={{ time() }}">
        @endforeach
    @endif

    <style>
        :root {
            --clr-theme-primary: {{ settings()->primaryColor ?? '#000' }} !important;
            --clr-theme-primary-2: {{ settings()->secondaryColor ?? '#EFEFEF' }} !important;
            --clr-theme-primary-3: {{ settings()->thirdColor ?? 'yellow' }} !important;
            --clr-theme-primary-4: {{ settings()->fourthColor ?? 'red' }} !important;
            --clr-theme-primary-5: {{ settings()->fifthColor ?? 'green' }} !important;
            --clr-theme-primary-6: {{ settings()->secondaryColor ?? '#EFEFEF' }} !important;
            --clr-theme-primary-7: {{ settings()->thirdColor ?? 'yellow' }} !important;
            --clr-theme-primary-8: {{ settings()->fourthColor ?? 'red' }} !important;
            --clr-theme-primary-9: {{ settings()->fifthColor ?? 'green' }} !important;
            --clr-theme-primary-10: {{ settings()->fifthColor ?? 'green' }} !important;
        }
    </style>

    <!-- CSS here -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/animate.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome-all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/odometer.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/meanmenu.css') }}" />
    <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/bootstrap-select/dist/css/bootstrap-select.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}?v={{ config('app.version') }}" />
    <link rel="manifest" href="{{ asset('pwa-manifest.json') }}?v={{ time() }}">

    @if(!empty(env('ANALYTICS_CDOE')))
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-PF2KLE1860"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{{ env('ANALYTICS_CDOE') }}');
        </script>
    @endif

    @livewireStyles
    @stack('style')
</head>
<body>
@if(request()->routeIs('frontend.home') && isChrome())
    <div class="pwa-app-install p-3 border border-secondary bg-secondary d-flex align-items-center justify-content-between d-none">
        @if(!empty($siteFavicon))
            <img class="bg-light border rounded-3 p-1" src="{{ asset($siteFavicon) }}" alt="{{ settingLocale('siteTitle') }}" style="height: 57px"/>
        @endif
        <p class="mb-0 px-3 py-1 text-white fw-medium fs-5">{{ __('Tek tıkla giriş için uygulamayı yükle!') }}</p>
        <a class="btn btn-dark header-btn px-3 text-center" href="/pwa">
            <i class="fa fa-download"></i>
        </a>
    </div>
@endif
<!-- sidebar-information-area-start -->
@includeIf('frontend.layouts.section.sidebar')
<!-- sidebar-information-area-end -->

<!-- header area start -->
@includeIf('frontend.layouts.section.header')
<!-- header area end -->

<main>
    @stack('breadcrumb')
    @yield('content')
</main>

<!-- footer area start -->
@includeIf('frontend.layouts.section.footer')
<!-- footer area end -->

@if(\App\Enums\StatusEnum::ACTIVE->value == settings()->floatWhatsapp)
    @includeIf('frontend.layouts.section.floating-button')
@endif

<!-- JS here -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.meanmenu.min.js') }}"></script>
<script src="{{ asset('assets/js/wow.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.nice-select.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.scrollUp.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/js/odometer.min.js') }}"></script>
<script src="{{ asset('assets/js/appear.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/plugins/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/app.js') }}?v={{ config('app.version') }}"></script>

@livewireScripts
<x-livewire-alert::scripts/>
@stack('script')
<script>
    $(document).ready(function() {
        Livewire.hook('element.init', ({ component, el }) => {
            $('.selectpicker').selectpicker();
        });
    });
</script>
@if($message = session('message'))
    <script>
        window.SweetAlert = Swal.mixin({
            target: "#page-container",
            showConfirmButton: false,
            showDenyButton: false,
            showCancelButton: false,
            showCloseButton: true,
            confirmButtonText: '{{ __('Tamam') }}',
            cancelButtonText: '{{ __('Vazgeç') }}',
            timer: 5000,
            timerProgressBar: true,
        });
        SweetAlert.fire({
            text: '{{ $message }}',
            icon: '{{ session('status') ?? 'success' }}',
            toast: true,
            position: 'top-right'
        });
    </script>
@endif
</body>
</html>
