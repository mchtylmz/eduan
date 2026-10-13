@props(['id', 'options', 'sort', 'order' => 'desc'])
<div class="col-12 col-md-3">
    <label class="form-label" for="{{ $id }}-column">{{ __('tables.sort_by') }}</label>
    <select id="{{ $id }}-column" class="form-select" data-table-sort>
        @foreach($options as $key => $label)<option value="{{ $key }}" @selected($key === $sort)>{{ $label }}</option>@endforeach
    </select>
</div>
<div class="col-12 col-md-3">
    <label class="form-label" for="{{ $id }}-order">{{ __('tables.sort_direction') }}</label>
    <select id="{{ $id }}-order" class="form-select" data-table-order>
        <option value="asc" @selected($order === 'asc')>{{ __('tables.ascending') }}</option>
        <option value="desc" @selected($order === 'desc')>{{ __('tables.descending') }}</option>
    </select>
</div>
