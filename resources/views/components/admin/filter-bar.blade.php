<form {{ $attributes->class(['admin-filter-bar', 'mb-4']) }} data-table-filters wire:ignore>
    <div class="row g-3 align-items-end">
        {{ $slot }}
        <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter me-1"></i>{{ __('Filtrele') }}</button>
            <button type="button" class="btn btn-alt-secondary" data-table-reset title="{{ __('tables.reset_filters') }}" aria-label="{{ __('tables.reset_filters') }}">
                <i class="fa fa-undo"></i>
            </button>
        </div>
    </div>
</form>
