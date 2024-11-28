<div>
    <div @class([
            'offcanvas fade bg-body-extra-light',
            'show' => $show == true,
            'offcanvas-top h-50' => $position == 'top',
            'offcanvas-start w-sm-75' => $position == 'left',
            'offcanvas-end w-sm-75' => $position == 'right',
            'offcanvas-start w-sm-50' => $position == 'md-left',
            'offcanvas-end w-sm-50' => $position == 'md-right',
            'offcanvas-bottom h-50' => $position == 'bottom',
            ])
         tabindex="-1"
         aria-modal="true"
         role="dialog"
         data-bs-scroll="true"
         data-bs-backdrop="false"
         data-bs-keyboard="false"
         id="livewireOffcanvas">
        <div class="offcanvas-header bg-body-light">
            <h5 class="offcanvas-title">{{ $title }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" wire:click.prevent="closeOffcanvas()"></button>
        </div>
        <div class="offcanvas-body">
            @if(is_string($component))
                @livewire($component, $data)
            @elseif(is_array($component))
                @foreach($component as $item) @livewire($item, $data) @endforeach
            @endif
        </div>
    </div>
    <div @class(['offcanvas-backdrop fade show' => $show == true]) data-bs-dismiss="offcanvas" wire:click.prevent="closeOffcanvas()"></div>
</div>
