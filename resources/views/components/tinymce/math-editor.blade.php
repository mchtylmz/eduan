@props([
    'id' => \Illuminate\Support\Str::slug($name ?? 'editor'),
    'name' => $name ?? 'editor',
    'placeholder' => $placeholder ?? __('İstenen içeri bu alana yazılabilir'),
    'value' => $value ?? '',
    'height' => intval($height ?? 500)
])

s
<div id="editorjs"></div>
<button id="save-button">Save</button>
<pre id="output"></pre>

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
    <script src="{{ asset('backend/assets/js/plugins/editorjs-mathlive-main/dist/bundle.js') }}"></script>
    <script>
        const editor = new EditorJS({
            readOnly:false,
            autofocus: true,

            tools:{
                math:{
                    class:MathEditor,
                    inlineToolbar:true
                }
            },
            "version": "2.26.4"
        });
    </script>
@endpush
