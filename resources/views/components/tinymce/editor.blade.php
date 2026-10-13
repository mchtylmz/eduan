@props([
    'id' => \Illuminate\Support\Str::slug($name ?? 'editor'),
    'name' => $name ?? 'editor',
    'placeholder' => $placeholder ?? __('İstenen içerik bu alana yazılabilir'),
    'value' => $value ?? '',
    'height' => intval($height ?? 500),
    'livewire' => boolval($livewire ?? true)
])
@once
@push('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/skins/ui/tinymce-5/content.min.css" />
    <style>
        .tox .tox-promotion {display: none !important;}
    </style>
@endpush
@endonce

<textarea
    id="editor{{ $id }}"
    class="form-control tinymce"
    name="{{ $name }}"
    @if($livewire)
        wire:key="{{ $name }}"
        wire:model="{{ $name }}"
    @endif
    placeholder="{{ $placeholder }}"
>{{ $value }}</textarea>

@once
@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/tinymce.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/media/plugin.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/image/plugin.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/help/js/i18n/keynav/{{ app()->getLocale() }}.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.2.1/plugins/fullscreen/plugin.min.js"></script>
@endpush
@endonce
@push('script')
    @if($livewire)
        <script>
            (() => {
                const textarea = document.getElementById(@js('editor'.$id));
                if (!textarea || tinymce.get(textarea.id)) return;
                tinymce.init({
                    license_key: 'gpl',
                    selector: `#${textarea.id}`,
                    width: "100%",
                    height: {{ $height }},
                    resize: true,
                    language_url: '{{ in_array(app()->getLocale(), ['nl', 'tr']) ? asset('backend/assets/langs/tinymce_'.app()->getLocale().'.js') : '' }}',
                    language: '{{ in_array(app()->getLocale(), ['nl', 'tr']) ? app()->getLocale() : 'en' }}',
                    powerpaste_allow_local_images: true,
                    toolbar_sticky: true,
                    autosave_restore_when_empty: false,
                    autosave_ask_before_unload: true,
                    autosave_interval: '5s',
                    image_caption: true,
                    fullscreen_native: true,
                    visual_table_class: 'tinymce-table',
                    plugins: [
                        'advlist', 'anchor', 'autolink',
                        'image', 'lists', 'link', 'media', 'preview',
                        'table', 'wordcount', 'quickbars', 'autosave', 'fullscreen'
                    ],
                    toolbar: 'fullscreen bold italic forecolor backcolor aligncenter bullist numlist',
                    content_style: 'body { font-family:Arial,sans-serif; font-size:16px }',
                    automatic_uploads: true,
                    file_picker_types: 'image',
                    font_size_formats: '10px 11px 12px 14px 16px 18px 24px 32px 36px 48px 64px',
                    font_size_input_default_unit: "px",
                    setup: function setup(editor) {
                        editor.on('init', () => editor.save());
                        editor.on('change input undo redo', function () {
                            editor.save();
                            @this.set(textarea.getAttribute('wire:model'), editor.getContent(), false);
                        });
                    },
                    file_picker_callback: (cb, value, meta) => {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');

                        input.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            if (!file) return;

                            const reader = new FileReader();
                            reader.addEventListener('load', () => {
                                const id = 'blobid' + (new Date()).getTime();
                                const blobCache =  tinymce.get(@js('editor'.$id)).editorUpload.blobCache;
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
            })();
        </script>
    @else
        <script>
            (() => {
                const textarea = document.getElementById(@js('editor'.$id));
                if (!textarea || tinymce.get(textarea.id)) return;
                tinymce.init({
                    license_key: 'gpl',
                    selector: `#${textarea.id}`,
                    width: "100%",
                    height: {{ $height }},
                    resize: true,
                    language_url: '{{ in_array(app()->getLocale(), ['nl', 'tr']) ? asset('backend/assets/langs/tinymce_'.app()->getLocale().'.js') : '' }}',
                    language: '{{ in_array(app()->getLocale(), ['nl', 'tr']) ? app()->getLocale() : 'en' }}',
                    powerpaste_allow_local_images: true,
                    toolbar_sticky: true,
                    autosave_restore_when_empty: false,
                    autosave_ask_before_unload: true,
                    autosave_interval: '5s',
                    image_caption: true,
                    menubar: true,
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
                        editor.on('init change input undo redo', function () {
                            editor.save();
                        });
                    },
                    file_picker_callback: (cb, value, meta) => {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');

                        input.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            if (!file) return;

                            const reader = new FileReader();
                            reader.addEventListener('load', () => {
                                const id = 'blobid' + (new Date()).getTime();
                                const blobCache =  tinymce.get(@js('editor'.$id)).editorUpload.blobCache;
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
            })();
        </script>
    @endif
@endpush
