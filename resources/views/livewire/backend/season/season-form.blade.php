<div>
    <div class="alert alert-warning d-flex gap-3 align-items-start mb-4">
        <i class="fa fa-calendar-check mt-1" aria-hidden="true"></i>
        <div><p class="mb-1">{{ __('Dönemlerin başlangıç ve bitiş tarihi aynı olamaz!') }}</p>
            <p class="mb-0">{{ __('Dönemler arasında aynı başlangıç ve bitiş tarihi olamaz!') }}</p></div>
    </div>
    @can('seasons:update')
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-success" wire:click="create"><i class="fa fa-plus me-2"></i>{{ __('Yeni Dönem Ekle') }}</button>
        </div>
    @endcan
    @if($seasons)
        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        @endif
        <form method="post" wire:submit="save">
            <div class="admin-season-grid">
            @foreach($seasons as $key => $season)
                <section class="admin-season-card" wire:key="season-card-{{ $key }}-{{ $season['id'] ?? 0 }}">
                    <input type="hidden" wire:model="seasons.{{ $key }}.key" />
                    <div class="admin-season-card-header">
                        <h3><i class="fa fa-calendar-days text-primary me-2" aria-hidden="true"></i>{{ $season['name'] ?: __('Yeni Dönem Ekle') }}</h3>
                        @can('seasons:delete')
                            <button type="button" class="btn btn-alt-danger" wire:click="delete({{ $key }})"
                                    wire:confirm="{{ __('Dönem kaydı silinecektir, onaylıyor musunuz?') }}" wire:loading.attr="disabled">
                                <i class="fa fa-trash-alt me-1"></i>{{ __('Kaldır / Sil') }}
                            </button>
                        @endcan
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label" for="season-name-{{ $key }}">{{ __('Dönem Adı') }}</label>
                            <input type="text" class="form-control" id="season-name-{{ $key }}" wire:model="seasons.{{ $key }}.name" placeholder="{{ __('Dönem Adı') }}">
                            <x-badge.error field="seasons.{{ $key }}.name" />
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="season-status-{{ $key }}">{{ __('Durum') }}</label>
                            <select id="season-status-{{ $key }}" class="form-select" wire:model="seasons.{{ $key }}.status">
                                @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)<option value="{{ $optionKey }}">{{ $optionText }}</option>@endforeach
                            </select>
                            <x-badge.error field="seasons.{{ $key }}.status" />
                        </div>
                        @foreach(['start_date' => __('tables.date_from'), 'end_date' => __('tables.date_to')] as $field => $label)
                            <div class="col-12 col-md-6">
                                <div wire:ignore>
                                    <label class="form-label" for="season-{{ $field }}-{{ $key }}">{{ $label }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-calendar" aria-hidden="true"></i></span>
                                        <input type="text" id="season-{{ $field }}-{{ $key }}" class="js-flatpickr form-control"
                                               wire:model="seasons.{{ $key }}.{{ $field }}" data-date-format="Y-m-d"
                                               data-locale="{{ app()->getLocale() }}" value="{{ $season[$field] ?? '' }}" placeholder="YYYY-MM-DD">
                                    </div>
                                </div>
                                <x-badge.error field="seasons.{{ $key }}.{{ $field }}" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
            </div>
            @can('seasons:update')
                <div class="admin-season-actions"><button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                    <i class="fa fa-save me-2"></i>{{ __('Tümünü Kaydet') }}
                </button></div>
            @endcan
        </form>
    @else
        <div class="text-center py-5 border rounded text-muted">
            <i class="fa fa-calendar-days fa-2x mb-3 d-block" aria-hidden="true"></i>
            <p class="mb-0">{{ __('management.no_seasons') }}</p>
        </div>
    @endif
</div>
