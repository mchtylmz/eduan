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
    @if(!empty($tinymce))
    <!-- tinymce -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/skins/ui/tinymce-5/content.min.css" />
    @endif
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
@if(!empty($tinymce))
<!-- tinymce -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/tinymce.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/media/plugin.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/image/plugin.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/help/js/i18n/keynav/tr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.5.1/plugins/fullscreen/plugin.min.js"></script>
@endif
<!-- app -->
<script src="{{ asset('backend/assets/app.js') }}?v={{ config('app.version') }}"></script>

@livewireScripts

<x-livewire-alert::scripts />
@stack('script')
<script>
    $(document).ready(function() {
        let livewireModal = document.getElementById('livewireModal');
        let livewireOffcanvas = document.getElementById('livewireOffcanvas');

        window.tinymce_shown = function() {
            if (tinymce.get('editorcontent')) {
                tinymce.get('editorcontent').remove();
            }

            tinymce.init({
                license_key: 'gpl',
                selector: '#editorcontent',
                width: "100%",
                height: '400px',
                resize: true,
                language_url: '{{ asset('backend/assets/langs/tinymce_tr.js') }}',
                language: '{{ app()->getLocale() }}',
                powerpaste_allow_local_images: true,
                toolbar_sticky: true,
                autosave_restore_when_empty: false,
                autosave_ask_before_unload: true,
                autosave_interval: '5s',
                image_caption: true,
                menubar: true,
                forced_root_block: false,
                fullscreen_native: true,
                visual_table_class: 'tinymce-table',
                plugins: [
                    'advlist', 'anchor', 'autolink',
                    'image', 'lists', 'link', 'media', 'preview',
                    'table', 'wordcount', 'quickbars', 'autosave', 'fullscreen'
                ],
                toolbar: 'fullscreen | undo redo | bold italic fontsize forecolor backcolor table alignleft aligncenter alignright alignjustify bullist numlist',
                content_style: 'body { font-family:Arial,sans-serif; font-size:16px }',
                automatic_uploads: true,
                file_picker_types: 'image',
                font_size_formats: '10px 11px 12px 14px 16px 18px 24px 32px 36px 48px 64px',
                font_size_input_default_unit: "px",
                setup: function setup(editor) {
                    editor.on('Change KeyUp', function () {
                        let content = editor.getContent();
                        let component = Livewire.find($('#editorcontent').closest('[wire\\:id]').attr('wire:id'));
                        component.set('content', content);
                    });
                },
                file_picker_callback: (cb, value, meta) => {
                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');

                    input.addEventListener('change', (e) => {
                        const file = e.target.files[0];

                        const reader = new FileReader();
                        reader.addEventListener('load', () => {
                            const id = 'blobid' + (new Date()).getTime();
                            const blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                            const base64 = reader.result.split(',')[1];
                            const blobInfo = blobCache.create(id, file, base64);
                            blobCache.add(blobInfo);
                            cb(blobInfo.blobUri(), { title: file.name });
                        });
                        reader.readAsDataURL(file);
                    });

                    input.click();
                },
            });
        }

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

            @if(!empty($tinymce))
            if (tinymce.get('editorcontent')) {
                tinymce.get('editorcontent').remove();
            }
            @endif
        });
        livewireModal.addEventListener('shown.bs.modal', function () {
            $('.selectpicker').selectpicker();

            @if(!empty($tinymce))
                window.tinymce_shown();
            @endif
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
        });
        Livewire.on('scrollToEndContent', function () {
            setTimeout(() => {
                window.scrollTo({
                    top: document.body.scrollHeight + 250,
                    behavior: 'smooth'
                });
            }, 100)
        });

        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                if ($('#livewireModal').is(':visible')) {
                    $('#livewireModal').trigger('shown.bs.modal'); // yeniden init etmek için tetikle
                }
            });
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
