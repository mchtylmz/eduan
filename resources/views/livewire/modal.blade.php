<div>
    <!-- Large Block Modal -->
    <div @class([
            'modal fade',
            'show' => $show == true
            ])
         @style([
         'display: block' => $show == true,
         'display: none' => $show != true,
         ])
         id="livewireModal"
         tabindex="-1"
         role="dialog"
         aria-labelledby="modal-xl"
         data-bs-keyboard="false"
         aria-hidden="true">
        <div class="modal-dialog modal-{{ $size }} modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="block block-rounded block-transparent mb-0">
                    <div class="block-header block-header-default">
                        <h3 class="block-title text-normal" style="letter-spacing: 1px">
                            {{ $title ?? '' }}
                        </h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-bs-dismiss="modal"
                                    wire:click.prevent="closeModal()" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content fs-sm pb-3">
                        @if(is_string($component))
                            @livewire($component, $data)
                        @elseif(is_array($component))
                            @foreach($component as $item) @livewire($item, $data) @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END Large Block Modal -->
</div>
