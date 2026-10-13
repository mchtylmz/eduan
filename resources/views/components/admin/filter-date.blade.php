@props(['id', 'name', 'label', 'value' => '', 'time' => false])
<div class="col-12 col-md-3">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
        class="form-control js-flatpickr" data-table-filter data-disable-mobile="true"
        data-date-format="{{ $time ? 'Y-m-d\\TH:i:S' : 'Y-m-d' }}" data-locale="{{ app()->getLocale() }}"
        @if($time) data-enable-time="true" data-enable-seconds="true" data-time_24hr="true" @endif
        placeholder="{{ __($time ? 'tables.choose_datetime' : 'tables.choose_date') }}">
</div>
