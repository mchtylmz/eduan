<div>
    <div class="row mb-1">
        <div class="col-lg-6 mb-3" wire:ignore>
            <label class="form-label" for="lesson_id">{{ __('Ders seçiniz') }}</label>
            <select id="lesson_id"
                    class="form-control selectpicker"
                    data-live-search="true"
                    data-size="6"
                    title="{{ __('Seçiniz') }}"
                    wire:model.live="lesson_id">
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach($this->lessons() as $lesson)
                    <option value="{{ $lesson->id }}">{{ $lesson->name }} ({{ $lesson->topics_count }} {{ __('Konu') }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="topic_id">{{ __('Konu seçiniz') }}</label>
            <select id="topic_id"
                    class="form-control selectpicker"
                    data-live-search="true"
                    data-size="6"
                    title="{{ __('Seçiniz') }}"
                    wire:model.live="topic_id">
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @if($topics = $this->topics())
                    @foreach($topics as $topic)
                        <option value="{{ $topic->id }}" @selected($topic->id == $topic_id)>{{ $topic->display_title }} ({{ $topic->questions_count }} {{ __('Soru') }})</option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>

    @if(count($questions = $this->questions()))
        <div class="row px-2 py-3">
            @foreach($questions as $question)
                <div class="col-lg-4 px-1">
                    <div class="block block-rounded border">
                        <div class="d-flex align-items-center justify-content-between p-0">
                            <h5 class="fs-base fw-semibold text-dark mb-0 py-2 px-3">
                                {{ __('Kodu') }}: {{ $question->code }}
                            </h5>
                            <span class="fw-medium text-dark mb-0 py-2 px-3">
                                {{ __('Dil') }}: {{ $question->language?->name }}
                            </span>
                        </div>
                        <div class="block-content p-0 bg-body-light">
                            <img class="w-100 object-fit-contain"
                                 style="height: 154px;"
                                 src="{{ asset($question->attachment_url) }}"
                                 alt="#{{ $question->id }}">
                        </div>
                        <div class="block-content text-center">
                            <h4 class="fs-base fw-semibold text-dark mb-0">
                                {{ $question->title }}
                            </h4>
                            <div class="answers mt-3">
                                <div class="row">
                                    @foreach($question->answers as $answer)
                                        <div class="col-4 px-1">
                                            <div class="block block-rounded mb-1">
                                                <div
                                                    class="block-content block-content-full border border-2 ribbon ribbon-modern ribbon-glass p-2 d-flex align-items-center justify-content-start">
                                                    @if(\App\Enums\YesNoEnum::YES->is($answer->correct))
                                                        <div class="ribbon-box bg-success">
                                                            <i class="fa fa-check mx-1"></i>
                                                        </div>
                                                    @endif
                                                    <div class="text-center">
                                                        <span class="px-3">{{ $answer->title }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="block-content block-content-full p-2">
                            <button type="button"
                                    data-bs-dismiss="modal"
                                    class="btn btn-sm btn-success w-100 h-100"
                                    wire:click="select({{ $question->id }})"
                                    wire:loading.attr="disabled">
                                <div wire:loading.remove>
                                    <i class="fa fa-fw fa-check-double mx-1"></i> {{ __('Seç ve Alana Ekle') }}
                                </div>
                                <div wire:loading>
                                    <i class="fa fa-fw fa-spinner fa-pulse"
                                       style="animation-duration: 0.6s"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $questions->links() }}</div>
    @elseif($topic_id && $lesson_id)
        <div class="alert alert-warning d-grid text-center mb-0">
            <i class="fa fa-question-circle fa-2x mb-2"></i>
            <p class="mb-2 fw-bold">
                {{ __('Ders ve konu için gösterilecek soru bulunmuyor.') }}
            </p>
        </div>
    @else
        <div class="alert alert-light d-grid text-center mb-0">
            <i class="fa fa-hand-pointer fa-2x mb-2"></i>
            <p class="mb-2 fw-bold">
                {{ __('Ders ve konu seçiniz.') }}
            </p>
        </div>
    @endif

</div>
