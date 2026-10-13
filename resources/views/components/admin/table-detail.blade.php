@props(['id', 'title', 'details', 'editUrl' => null, 'icon' => 'fa-circle-info', 'recordName' => null, 'recordCode' => null, 'metrics' => []])

<div class="offcanvas offcanvas-end admin-table-detail" tabindex="-1" id="{{ $id }}"
    aria-labelledby="{{ $id }}-title" wire:ignore.self data-table-detail-panel>
    <div class="offcanvas-header bg-body-light">
        <h2 class="offcanvas-title h5 mb-0 d-flex align-items-center gap-2" id="{{ $id }}-title">
            <i class="fa {{ $icon }} text-primary" aria-hidden="true"></i>{{ $title }}
        </h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('tables.close') }}"></button>
    </div>
    <div class="offcanvas-body">
        @if($recordName !== null)
            <div class="admin-detail-heading mb-4">
                <span class="admin-detail-icon bg-primary-lighter text-primary"><i class="fa {{ $icon }}" aria-hidden="true"></i></span>
                <div class="flex-grow-1">
                    <p class="text-muted mb-1 small">{{ __('tables.read_only') }}</p>
                    <h3 class="h4 mb-1">{{ $recordName }}</h3>
                    @if($recordCode)<p class="text-muted mb-0">{{ $recordCode }}</p>@endif
                </div>
            </div>
        @endif
        @if($metrics)
            <div class="admin-detail-metrics mb-4">
                @foreach($metrics as $label => $value)
                    <div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
                @endforeach
            </div>
        @endif
        @if($details)
        <section class="admin-detail-section mb-4" aria-label="{{ $title }}">
            <dl class="admin-table-detail-list mb-0">
                @foreach($details as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value ?? '-' }}</dd></div>
                @endforeach
            </dl>
        </section>
        @endif
        {{ $slot }}
        @if($editUrl)
            <div class="admin-detail-footer"><a href="{{ $editUrl }}" class="btn btn-warning"><i class="fa fa-pen me-1"></i>{{ __('Düzenle') }}</a></div>
        @endif
    </div>
</div>
