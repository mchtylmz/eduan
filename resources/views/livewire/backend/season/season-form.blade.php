<div>

    <div class="alert alert-warning my-3">
        <p class="mb-1 fw-bold">{{ __('Dönemlerin başlangıç ve bitiş tarihi aynı olamaz!') }}</p>
        <p class="mb-1 fw-bold">{{ __('Dönemler arasında aynı başlangıç ve bitiş tarihi olamaz!') }}</p>
    </div>
    <hr>

    @can('seasons:update')
        <button type="button" class="btn btn-success mb-3" wire:click="create">
            <i class="fa fa-fw fa-plus mx-1"></i> {{ __('Yeni Dönem Ekle') }}
        </button>
    @endcan

    @if($seasons)
        @if($errors->any())
            <div class="mb-3">
                @foreach ($errors->all() as $error)
                    <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <form class="p-3 my-1 border border-default" method="post" wire:submit="save">
            @foreach($seasons as $key => $season)
                <input type="hidden" wire:model="seasons.{{ $key }}.key" />
                <div class="row justify-content-center align-items-end">
                    <div class="col-lg-8 mb-3">
                        <label class="form-label" for="name">{{ __('Adı') }}</label>
                        <input type="text" class="form-control" id="name" wire:model="seasons.{{ $key }}.name"
                               placeholder="{{ __('Adı') }}.."/>
                        <x-badge.error field="seasons.{{ $key }}.name"/>
                    </div>
                    <div class="col-lg-4 mb-3 text-end">
                        @can('seasons:delete')
                            <button type="button" class="btn btn-alt-danger px-4"
                                    wire:click="delete({{ $key }})"
                                    wire:confirm="{{ __('Dönem kaydı silinecektir, onaylıyor musunuz?') }}"
                                    wire:loading.attr="disabled">
                                <div wire:loading.remove>
                                    <i class="fa fa-trash-alt mx-2 fa-faw"></i> {{ __('Kaldır / Sil') }}
                                </div>
                                <div wire:loading>
                                    <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                                </div>
                            </button>
                        @endcan
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div wire:ignore>
                            <label class="form-label" for="start_date">{{ __('Başnlangıç Tarihi') }}</label>
                            <input type="text" class="js-flatpickr form-control" id="start_date" wire:model="seasons.{{ $key }}.start_date"
                                   placeholder="YYYY-AA-GG"
                                   data-locale="{{ $locale }}" value="{{ $season['start_date'] ?? '' }}">
                        </div>
                        <x-badge.error field="seasons.{{ $key }}.start_date"/>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div wire:ignore>
                            <label class="form-label" for="end_date">{{ __('Bitiş Tarihi') }}</label>
                            <input type="text" class="js-flatpickr form-control" id="end_date" wire:model="seasons.{{ $key }}.end_date"
                                   placeholder="YYY-AA-GG"
                                   data-locale="{{ $locale }}" value="{{ $season['end_date'] ?? '' }}">
                        </div>
                        <x-badge.error field="seasons.{{ $key }}.end_date"/>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label class="form-label" for="status">{{ __('Durum') }}</label>
                        <select id="status" class="form-control" wire:model="seasons.{{ $key }}.status">
                            @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                                <option value="{{ $optionKey }}">{{ $optionText }}</option>
                            @endforeach
                        </select>
                        <x-badge.error field="seasons.{{ $key }}.status"/>
                    </div>
                </div>

                <div class="bg-light py-1 my-2"></div>
            @endforeach

            <div class="my-3 text-center">
                @can('seasons:update')
                    <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Tümünü Kaydet') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                        </div>
                    </button>
                @endcan
            </div>
        </form>
    @endif
</div>
