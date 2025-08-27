<div>

    <div class="exam-question pb-3 ps-3">

        <div class="d-flex justify-content-end gap-3">
            <div class="mb-3 text-end p-0">
                <a target="_blank" href="{{ route('frontend.ai.solution', $answerAI->question->code) }}"
                   class="btn btn-alt-primary px-4">
                    <i class="fa fa-eye mx-2 fa-faw"></i> {{ __('Sitede Görüntüle') }}
                </a>
            </div>
            @can('ai:delete')
                <div class="mb-3 text-end p-0">
                    <button type="button" class="btn btn-alt-danger px-6" wire:loading.attr="disabled"
                            wire:click="delete"
                            wire:confirm="{{ __('Yanıt kalıcı olarak silinecektir, işleme devam edilsin mi?') }}">
                        <div wire:loading.remove>
                            <i class="fa fa-trash mx-2 fa-faw"></i> {{ __('Yanıtı Sil') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                        </div>
                    </button>
                </div>
            @endcan
        </div>
        <hr>

        <div class="row">
            <div class="col-lg-6">
                <div class="title text-start">
                    <p class="mb-3">{{ __('Yanıt Dili') }}: {{ $answerAI->language->name }}</p>
                    <hr>
                </div>
                <div class="title text-start">
                    <h5 class="mb-3">{{ $answerAI->question->title }}</h5>
                </div>
                <div class="attachment text-left">
                    @if($attachment = $answerAI->question->attachment)
                        <img class="w-100"
                             style="max-width: 600px; object-fit: contain"
                             src="{{ getImage($attachment) }}"
                             alt="{{ __('Soru') }}"/>
                    @endif
                </div>

                <div class="solution mt-3">
                    <div class="attachment text-left">
                        <img class="w-100"
                             style="max-width: 600px; object-fit: contain"
                             src="{{ getImage($answerAI->question->solution) }}?v={{ time() }}"
                             alt="{{ __('Soru Çözümü') }}"/>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <p class="mb-3">{{ __('Yapay Zeka Yanıtı') }}</p>
                <hr>
                <x-ai.steps :answerAi="$answerAI" />
            </div>
        </div>

    </div>

</div>
