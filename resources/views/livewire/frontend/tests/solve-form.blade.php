<div class="solve-form">

    <div class="bg-light p-3">
        <h5 class="mb-0">{{ $questionIndex }}. {{ __('Soru') }}</h5>
    </div>

    <div class="exam-question py-3 ps-3">
        <div class="title text-start">
            <h5 class="mb-3">{{ $question->title }}</h5>
        </div>

        <div class="attachment text-center">
            @if($attachment = $question->attachment)
            <img class="w-100"
                 style="max-width: 600px; object-fit: contain"
                 src="{{ getImage($attachment) }}"
                 alt="{{ __('Soru') }}"/>
            @endif
        </div>

        <div class="answers my-3 py-3">
            <div class="row align-items-center justify-content-center">
                @foreach($question->answers as $answer)
                    <div class="col-3 col-sm-2 my-2">
                        <input type="radio"
                               class="btn-check"
                               wire:model.live="results.{{ $questionIndex }}.userAnswer"
                               id="answer{{ $answer->id }}"
                               autocomplete="off"
                               value="{{ $answer->id }}" @disabled($this->isDisabled())>
                        <label @class([
                                  'label-check btn btn-outline-dark answer w-100 p-3 rounded-0 fw-bold text-nowrap',
                                  'correct' => $this->evaluateAnswer($answer->id)
                               ])
                               style="font-size: 1.1rem"
                               for="answer{{ $answer->id }}">
                            <!-- <span>{{ letters($loop->index) }})</span> -->
                            <span>{{ $answer->title }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="solution">
            @if(!empty($results[$questionIndex]['userAnswer']))
                @php($evaluateAnswer = $this->evaluateAnswer($results[$questionIndex]['userAnswer']))
                <div class="bg-light p-3 mt-3 d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ $questionIndex }}. {{ __('Soru Çözümü') }}</h5>
                    <div>
                        <strong class="me-1 text-dark">{{ __('Yanıtınız') }}: </strong>
                        <strong @class([
                                'text-white py-1 px-2',
                                'bg-danger' => !$evaluateAnswer,
                                'bg-success' => $evaluateAnswer,
                            ])>{{ $evaluateAnswer ? __('Doğru') : __('Yanlış') }}</strong>
                    </div>
                </div>

                <div class="attachment text-center">
                    <img class="w-100"
                         style="max-width: 600px; object-fit: contain"
                         src="{{ getImage($question->solution) }}"
                         alt="{{ __('Soru Çözümü') }}"/>
                </div>
            @endif
        </div>

    </div>

    @if(!empty($results[$questionIndex]['userAnswer']))
        <div class="row align-items-center justify-content-between border-top pt-3">

            <div class="col-6 col-lg-4">
                @if($questionIndex > 1)
                    <button class="btn btn-secondary py-3 px-2 w-100 fw-semibold text-white"
                            wire:click="previousPage" @disabled($questionIndex <= 0)>
                        <i class="fa fa-fw fa-chevron-double-left me-1"></i>
                        <span>{{ __('Önceki Soru') }}</span>
                    </button>
                @endif
            </div>

            <div class="col-6 col-lg-4">
                @if($questionIndex < $questionsCount)
                    <button class="btn btn-secondary py-3 px-2 w-100 fw-semibold text-white"
                            wire:click="nextPage" @disabled($questionIndex >= $questionsCount)>
                        <span>{{ __('Sonraki Soru') }}</span>
                        <i class="fa fa-fw fa-chevron-double-right ms-1"></i>
                    </button>
                @endif

                @if($questionIndex == $questionsCount)
                    <button class="btn btn-success py-3 px-2 w-100 fw-semibold text-white"
                            wire:loading.attr="disabled"
                            wire:click="complete">
                        <div wire:loading.remove>
                            <i class="fa fa-fw fa-flag-checkered me-1"></i>
                            <span>{{ __('Testi Tamamla') }}</span>
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                        </div>
                    </button>
                @endif
            </div>
        </div>
    @endif

</div>
