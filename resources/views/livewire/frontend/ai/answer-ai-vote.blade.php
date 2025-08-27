<div>
    <div class="bg-light px-3 py-2">
        @can('ai:solution-vote')
            <div class="d-flex align-items-center justify-content-end gap-2">
                <h6 class="mb-0 fw-bold me-3">{{ __('Yanıtı Değerlendir :') }} </h6>
                <!-- stars -->
                <div class="half-stars-vote">
                    <div class="star-rating animated-stars">
                        <input type="radio" id="star5-{{ $answerAI->id }}" wire:model.change="vote" value="5">
                        <label for="star5-{{ $answerAI->id }}" class="fa fa-star"></label>
                        <input type="radio" id="star4-{{ $answerAI->id }}" wire:model.change="vote" value="4">
                        <label for="star4-{{ $answerAI->id }}" class="fa fa-star"></label>
                        <input type="radio" id="star3-{{ $answerAI->id }}" wire:model.change="vote" value="3">
                        <label for="star3-{{ $answerAI->id }}" class="fa fa-star"></label>
                        <input type="radio" id="star2-{{ $answerAI->id }}" wire:model.change="vote" value="2">
                        <label for="star2-{{ $answerAI->id }}" class="fa fa-star"></label>
                        <input type="radio" id="star1-{{ $answerAI->id }}" wire:model.change="vote" value="1">
                        <label for="star1-{{ $answerAI->id }}" class="fa fa-star"></label>
                    </div>
                </div>
                <!-- stars -->
            </div>
        @endcan
    </div>

    @if(!$report)
        <div class="d-flex align-items-center justify-content-end my-2">
            <a href="javascript:void(0)" class="text-decoration-underline text-black"
               wire:click="sendReport"
               wire:confirm="{{ __('Yapay zeka yanıtı hatalı olarak raporlanacak, işlemin geri dönüşü bulunmuyor, onaylıyor musunuz?') }}">
                <small>{{ __('Yanıtı Raporla (Hatalı)') }}</small>
            </a>
        </div>
    @endif
</div>
