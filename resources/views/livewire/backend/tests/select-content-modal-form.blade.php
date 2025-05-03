<div>
    <style>
        .tox .tox-promotion {display: none !important;}
    </style>

    <form wire:submit.prevent="save" novalidate>

        <div class="mb-3" wire:ignore>
            <label class="form-label" for="content">{{ __('İçerik Metni') }}</label>
            <textarea
                id="editorcontent"
                class="form-control tinymce custom_content"
                name="content"
                wire:key="content"
                wire:model.defer="content"
                placeholder="{{ __('İçerik Metni') }}"
            >{!! $content !!}</textarea>
            <x-badge.error field="content"/>
        </div>

        <div class="mb-3 mt-0 text-center py-2">
            <button type="submit" data-bs-dismiss="modal" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
