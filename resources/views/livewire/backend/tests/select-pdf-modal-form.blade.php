<div>
    <form wire:submit.prevent="save" novalidate>

        <div class="mb-3">
            <label class="form-label" for="file">{{ __('PDF Dosyası') }}</label>
            <input type="file" class="form-control"
                   id="file"
                   wire:model="file"
                   accept="application/pdf,.pdf"
                   placeholder="{{ __('PDF Dosyası') }}.."/>
            <x-badge.error field="file"/>
        </div>

        <div class="mb-3 mt-0 text-center py-2">
            <button type="submit" data-bs-dismiss="modal" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Dosyayı Kaydet ve Seç') }}
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
