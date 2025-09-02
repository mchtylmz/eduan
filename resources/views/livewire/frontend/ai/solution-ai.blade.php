<div>
    @if($warningMessage)
        <div class="alert alert-warning fw-bold px-3 py-2 my-3">
            {{ $warningMessage }}
        </div>
    @endif

    @if(!empty($answerAi))
        <br>
        <x-ai.steps :answerAi="$answerAi"/>

        <div class="answer-ai-vote">
            @livewire('frontend.ai.answer-ai-vote', ['answerAI' => $answerAi, 'user' => user()])
        </div>

        @if($warningTranslateAiText = __('Yapay Zeka yanıt alt alan açıklama metni'))
            <div class="alert alert-warning p-2 my-1">{{ $warningTranslateAiText }}</div>
        @endif

    @endif

    @if(empty($answerAi) && empty($warningMessage))
        <div class="row justify-content-center mt-3">
            <div class="col-lg-10">
                <button class="btn btn-success py-2 px-2 w-100 fw-semibold text-white solution-ai-button"
                        wire:click="askGpt"
                        wire:loading.attr="disabled" @disabled($disabledAskGpt)>
                    <div wire:loading.remove>
                        <i class="fa fa-fw fa-wand-magic-sparkles me-1"></i>
                        <span>{{ __('Yapay Zeka ile Çözümü Yeniden Anlat') }}</span>
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: .7s;"></i>
                        <span>{{ __('Çözüm yapılıyor, lütfen bekleyiniz..') }}</span>
                    </div>
                </button>
            </div>
        </div>
    @endif

</div>
