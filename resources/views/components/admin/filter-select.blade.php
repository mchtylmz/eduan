@props(['name', 'label', 'options', 'multiple' => false, 'value' => '', 'id', 'dropdownSize' => 'auto', 'allowAll' => true])
<div class="col-12 col-md-3">
    <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" data-table-filter
        @class(['form-select' => !$multiple, 'selectpicker' => $multiple])
        @if($multiple) multiple data-live-search="true" data-actions-box="true" data-width="100%" data-size="{{ $dropdownSize }}" title="{{ __('Tümü') }}" @endif>
        @if(!$multiple && $allowAll)<option value="">{{ __('Tümü') }}</option>@endif
        @foreach($options as $key => $text)
            <option value="{{ $key }}" @selected(!$multiple && (string) $value === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
</div>
