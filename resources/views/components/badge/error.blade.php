@props([
    'field' => $field ?? false,
    'class' => $class ?? ''
])
@error($field)
<div class="text-danger text-sm px-2 py-0 fw-bold {{ $class }}">{{ $message ?? '' }}</div>
@enderror
