@if(settings()->{'popupStatus_'.app()->getLocale()} == \App\Enums\StatusEnum::ACTIVE->value)
    <!-- Modal -->
    <div class="modal fade" id="PromoModal" tabindex="-1" aria-labelledby="PromoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                @if($popupSubject = settings()->{'popupSubject_'.app()->getLocale()})
                    <div class="modal-header">
                        <h5 class="modal-title" id="staticBackdropLabel">{{ $popupSubject }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                @endif
                <div class="modal-body">
                    @if(empty($popupSubject))
                        <div class="content-close">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    @endif
                    @if($popupContent = settings()->{'popupContent_'.app()->getLocale()})
                        <div class="popup-content">
                            {!! $popupContent !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->

    @if(!empty($popupContent) || !empty($popupSubject))
        <script>
            let promoTime = Math.floor(Date.now() / 1000) + (3600 * 36) + 120;

            setTimeout(() => {
                if (window.localStorage.getItem("PromoModal_storage") <= '{{ strtotime('36 hours') }}') {
                    $('#PromoModal').modal('show');
                }
            }, 100);

            let PromoModal = document.getElementById('PromoModal');
            PromoModal.addEventListener('hidden.bs.modal', function (event) {
                window.localStorage.setItem("PromoModal_storage", promoTime);
            })
        </script>
    @endif
@endif
