<div>
    <form wire:submit="attach">
        <div class="row align-items-end">

            <div class="col-lg-4 mb-3" wire:ignore>
                <label class="form-label" for="userId">{{ __('Kullanıcılar') }}</label>
                <select id="userId"
                        class="form-control selectpicker"
                        data-live-search="true"
                        data-size="10"
                        wire:model="userId">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach(data()->filters()->students() as $id => $name)
                        <option value="{{ $id }}">{{ $name  }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 mb-3">
                <button type="submit" class="btn btn-alt-primary w-100 px-4 mt-3" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-fw fa-plus me-1 opacity-50"></i> {{ __('Ekle') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>

        </div>
    </form>
</div>
