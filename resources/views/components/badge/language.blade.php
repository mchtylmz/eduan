@if(!empty($language))
<div class="bg-body-light p-2 px-3">
    <h5 class="mb-0">{{ str()->upper($language->code) }} - {{ $language->name }}</h5>
</div>
@endif
