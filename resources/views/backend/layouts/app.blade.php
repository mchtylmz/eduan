<!doctype html>
<html lang="{{ app()->getLocale() }}" class="{{ user()?->getMeta('darkMode', 0) ? 'page-header-dark dark-mode' : ''}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-version" content="{{ config('app.version') }}">

    <title>{{ !empty($title) ? $title . ' | ' : '' }}{{ settingLocale('siteTitle') }}</title>
    @if($favicon = settings()->siteFavicon)
        <link rel="shortcut icon" href="{{ asset($favicon) }}">
    @endif

    <!-- sweetalert2 -->
    <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <!-- dropify -->
    <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/dropify/dist/css/dropify.min.css') }}">
    <!-- flatpickr -->
    <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/flatpickr/flatpickr.min.css') }}">
    <!-- bootstrap-select -->
    <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/bootstrap-select/dist/css/bootstrap-select.min.css') }}" />
    <!-- oneui -->
    <link rel="stylesheet" href="{{ asset('backend/assets/css/oneui.min.css') }}">
    <!-- app -->
    <link rel="stylesheet" href="{{ asset('backend/assets/app.css') }}?v={{ config('app.version') }}">

    <style>
        [x-cloak] { display: none !important; }
    </style>
    @livewireStyles

    @stack('style')
</head>
<body>
@auth
    @includeIf('backend.layouts.page-container')
@else
    @includeIf('backend.layouts.page-auth')
@endauth

<livewire:modal />
<livewire:offcanvas />
<livewire:events />

<script>
    const lang = {
      ok: '{{ __('Tamam') }}',
      cancel: '{{ __('Vazgeç') }}',
    };
</script>
<!-- oneui -->
<script src="{{ asset('backend/assets/js/oneui.app.min.js') }}"></script>
<!-- jQuery -->
<script src="{{ asset('backend/assets/js/lib/jquery.min.js') }}"></script>
<!-- jquery.validate -->
<script src="{{ asset('backend/assets/js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/plugins/jquery-validation/localization/messages_'.app()->getLocale().'.min.js') }}"></script>
<!-- sweetalert2 -->
<script src="{{ asset('backend/assets/js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- dropify -->
<script src="{{ asset('backend/assets/js/plugins/dropify/dist/js/dropify.js') }}"></script>
<!-- flatpickr -->
<script src="{{ asset('backend/assets/js/plugins/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/plugins/flatpickr/l10n/'.app()->getLocale().'.js') }}"></script>
<!-- jquery.maskedinput -->
<script src="{{ asset('backend/assets/js/plugins/jquery.maskedinput/jquery.maskedinput.min.js') }}"></script>
<!-- bootstrap-select -->
<script src="{{ asset('backend/assets/js/plugins/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
<script src="{{ asset('backend/assets/js/plugins/bootstrap-select/dist/js/i18n/defaults-'.app()->getLocale().'_'.str(app()->getLocale())->upper().'.min.js') }}"></script>
<!-- app -->
<script src="{{ asset('backend/assets/app.js') }}?v={{ config('app.version') }}"></script>

@livewireScripts

<x-livewire-alert::scripts />
@stack('script')

<script>
    $(document).ready(function() {
        let livewireModal = document.getElementById('livewireModal');
        let livewireOffcanvas = document.getElementById('livewireOffcanvas');

        Livewire.on('showModal', function () {
            let modal = new bootstrap.Modal(livewireModal);
            modal.show();
        });
        Livewire.on('closeModal', function () {
            let modal = new bootstrap.Modal(livewireModal);
            modal.hide();
        });
        livewireModal.addEventListener('hidden.bs.modal', function () {
            Livewire.dispatch('closeModal');
        });
        livewireModal.addEventListener('shown.bs.modal', function () {
            $('.selectpicker').selectpicker();
        });

        Livewire.on('showOffcanvas', function () {
            let offcanvas = new bootstrap.Offcanvas(livewireOffcanvas);
            offcanvas.show();
        });
        Livewire.on('closeOffcanvas', function () {
            let offcanvas = new bootstrap.Offcanvas(livewireOffcanvas);
            offcanvas.hide();
        });
        livewireOffcanvas.addEventListener('hide.bs.offcanvas', function () {
            Livewire.dispatch('closeOffcanvas');
        });
        livewireOffcanvas.addEventListener('show.bs.offcanvas', function () {
            $('.selectpicker').selectpicker();
        });
        Livewire.hook('element.init', ({ component, el }) => {
            $('.selectpicker').selectpicker();
        })
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
