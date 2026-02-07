<div>
    @if($errors->any())
        <div class="mb-3">
            @foreach ($errors->all() as $error)
                <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
            @endforeach
        </div>
    @endif

    <form wire:submit.prevent="run" novalidate>
        <div class="row">
            <div class="col-lg-4 mb-3" wire:ignore>
                <label class="form-label" for="start_at">{{ __('Başlangıç Zamanı') }}</label>
                <input type="text" class="js-flatpickr form-control" id="start_at" wire:model="start_at" data-enable-time="true" data-time_24hr="true" data-locale="{{ $locale }}" value="{{ $start_at }}">
            </div>
            <div class="col-lg-4 mb-3" wire:ignore1>
                <label class="form-label" for="end_at">{{ __('Bitiş Zamanı') }}</label>
                <input type="text" class="js-flatpickr form-control" id="end_at" wire:model="end_at" data-enable-time="true" data-time_24hr="true" data-locale="{{ $locale }}" value="{{ $end_at }}">
            </div>
        </div>

        @can('leagues:run')
            <div class="mb-3 text-center py-2">
                <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-paper-plane mx-2 fa-faw"></i> {{ __('Çalıştır') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                    </div>
                </button>
            </div>
        @endcan
    </form>

</div>
