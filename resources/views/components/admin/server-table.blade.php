@props(['id', 'url', 'sortName' => 'sort', 'sortOrder' => 'asc', 'pageSize' => 15, 'minWidth' => '900px', 'cardView' => false])

@once
    @push('style')
        <link rel="stylesheet" href="{{ asset('backend/assets/js/plugins/bootstrap-table/dist/bootstrap-table.min.css') }}">
        <link rel="stylesheet" href="{{ asset('backend/assets/server-table.css') }}?v={{ config('app.version') }}">
    @endpush
    @push('script')
        <script src="{{ asset('backend/assets/js/plugins/bootstrap-table/dist/bootstrap-table.min.js') }}"></script>
        <script src="{{ asset('backend/assets/js/plugins/bootstrap-table/dist/locale/bootstrap-table-'.(['nl' => 'nl-NL', 'en' => 'en-US', 'tr' => 'tr-TR', 'fr' => 'fr-FR'][app()->getLocale()] ?? 'en-US').'.min.js') }}"></script>
        <script src="{{ asset('backend/assets/server-table.js') }}?v={{ config('app.version') }}"></script>
    @endpush
@endonce

<div class="alert alert-danger d-none" role="alert" data-table-error wire:ignore></div>
<div class="table-responsive admin-server-table" style="--admin-server-table-min-width: {{ $minWidth }};" wire:ignore>
    <table id="{{ $id }}" {{ $attributes->class(['table', 'table-vcenter', 'table-hover', 'mb-0']) }}
        data-url="{{ $url }}" data-side-pagination="server" data-pagination="true"
        data-pagination-parts='["pageInfo","pageList"]' data-page-size="{{ $pageSize }}" data-page-list="{{ $pageSize == 5 ? '[5,15,25,50,100]' : '[15,25,50,100]' }}" data-search="true"
        data-search-align="right" data-buttons-align="left" data-show-refresh="true"
        data-icons-prefix="fa" data-icons='{"refresh":"fa-sync-alt"}' data-id-field="id"
        data-sort-name="{{ $sortName }}" data-sort-order="{{ $sortOrder }}" data-escape="false"
        data-locale="{{ ['nl' => 'nl-NL', 'en' => 'en-US', 'tr' => 'tr-TR', 'fr' => 'fr-FR'][app()->getLocale()] ?? 'en-US' }}">
        {{ $slot }}
    </table>
    @if($cardView)
        <div data-user-cards class="admin-user-cards d-none" aria-live="polite"></div>
    @endif
</div>
