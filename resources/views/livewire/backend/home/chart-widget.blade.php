<div>
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title ?? '' }}</h3>
        </div>
        <div class="block-content pt-2">
            <div id="loading_{{ $id }}">
                <x-livewire.lazy_placeholder />
            </div>

            <div id="{{ $id }}"></div>
        </div>
    </div>
</div>
