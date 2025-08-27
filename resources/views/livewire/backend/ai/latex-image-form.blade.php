<div>
    @if($errors->any())
        <div class="mb-3">
            @foreach ($errors->all() as $error)
                <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
            @endforeach
        </div>
    @endif

    <div class="text-center p-3">
        <img src="{{ getImage($latexImage->image) }}" style="max-width: 400px;" alt="{{ $latexImage->formula }}"/>
    </div>
    <hr>

    <form class="my-3 " wire:submit="save" novalidate>

        <div class="mb-3" wire:ignore>
            <label class="form-label" for="image">{{ __('Latex Görseli') }}</label>
            <input type="file" class="dropify" id="image" wire:model="image"
                   data-show-remove="false"
                   data-show-errors="true"
                   data-allowed-file-extensions="jpg png jpeg webp"
                   accept=".jpg,.jpeg,.png,.webp"
                   data-max-file-size="10M"
            />
        </div>

        <div class="mb-3 text-center py-2">
            <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
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
