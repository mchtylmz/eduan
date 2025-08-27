@props([
    'location' => $attributes['location'] ?? false,
    'image' => $attributes['image'] ?? false,
    'alt' => $alt ?? false,
    'class' => $class ?? 'question-image',
])
@if(!empty($image))
    <a class="text-dark text-decoration-underline d-flex align-items-center" target="_blank" href="{{ $location ?: $image }}">
        <i class="fa fa-fw fa-external-link me-3"></i>
        <img class="{{ $class }}" src="{{ $image }}" alt="{{ $alt ?: $image }}" />
    </a>
@endif
