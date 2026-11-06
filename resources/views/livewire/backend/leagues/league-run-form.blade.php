<div>
    <form wire:submit="run" novalidate>
        <div class="mb-4">
            <label class="form-label" for="league-mode">{{ __('leagues.mode') }}</label>
            <select id="league-mode" class="form-select" wire:model="is_live">
                <option value="" disabled hidden @selected($is_live === '')>{{ __('leagues.mode_placeholder') }}</option>
                <option value="0">{{ __('leagues.status_single') }}</option>
                <option value="1">{{ __('leagues.status_live') }}</option>
            </select>
            <x-badge.error field="is_live" />
            <p class="form-text mb-0">{{ __('leagues.mode_help') }}</p>
        </div>
        <div class="row">
            @foreach(['start_at' => __('Başlangıç Zamanı'), 'end_at' => __('Bitiş Zamanı')] as $field => $label)
                <div class="col-md-6 mb-3">
                    <div wire:ignore>
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <input type="text" class="js-flatpickr form-control" id="{{ $field }}" wire:model="{{ $field }}"
                               data-enable-time="true" data-time_24hr="true" data-locale="{{ app()->getLocale() }}" value="{{ $this->$field }}">
                    </div>
                    <x-badge.error :field="$field" />
                </div>
            @endforeach
        </div>
        <p class="fs-sm text-muted">{{ __('leagues.period_help') }}</p>
        <button type="submit" class="btn btn-alt-primary w-100" wire:loading.attr="disabled" wire:target="run">
            <i class="fa fa-fw fa-spinner fa-pulse me-1" wire:loading wire:target="run" aria-hidden="true"></i>
            {{ __('leagues.create') }}
        </button>
    </form>
</div>
