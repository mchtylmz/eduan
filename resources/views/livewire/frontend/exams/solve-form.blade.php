<div class="px-3" wire:init="startedExams">
    @php $content = $this->getContent(); @endphp
    <div class="bg-light exams-header border-top pt-3 pb-2 px-4 row" style="position: sticky; top: 0; z-index: 123">
        <div class="col-lg-6 exams-title text-start mb-1 d-flex align-items-center gap-1">
            <p class="my-0 text-dark fw-bold">{{ $test->name }}</p>
            @if(!empty($content))
                <p class="my-0 fw-bold mx-2">/</p>
                <p class="my-0 text-dark fw-bold">
                    @if(\App\Enums\TestSectionTypeEnum::QUESTION->is($content->type))
                        <span>{{ __('Soru') }}</span>
                    @else
                        {{ $content->name }}
                    @endif
                </p>
            @endif
            <div class="text-center px-2 fw-medium text-dark bg-warning  ms-1" style="min-width: 200px;" wire:loading>
                <i class="fa fa-spinner fa-pulse mx-1 fw-bold" style="animation-duration: .5s;"></i>
                <span>{{ __('Yükleniyor') }}....</span>
            </div>
        </div>
        <div class="col-lg-6 exams-duration text-sm-end d-flex align-items-center justify-content-around gap-2 mb-1">
            <div class="counter-progress d-flex align-items-center gap-2 w-35" style="height: 14px">
                <span
                    class="progress-text text-dark">{{ $this->resultQuestionsCount() }} / {{ $totalQuestionsCount }}</span>
                <div class="progress w-50">
                    <div class="progress-bar progress-bar-striped bg-success" role="progressbar"
                         style="width: {{ $this->resultQuestionsPercent() }}%"
                         aria-valuenow="{{ $this->resultQuestionsPercent() }}" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>
            <div class="exams-timer w-50" wire:ignore>
                <span><i class="fa fa-clock mx-2"></i>{{ __('Süre') }}:</span>
                <span id="exams-timer-text" class="text-dark fw-medium"
                      data-timer="{{ $expirationTime }}">
                    <i class="fa fa-hourglass-start fa-pulse mx-1"></i>
                </span>
            </div>
        </div>
    </div>

    <div class="border exams-sections row">
        <div class="col-lg-2 bg-light py-1 exams-sections-left">
            <div class="accordion" id="accordionExamSections" style="position: sticky; top: 60px; z-index: 122">
                @foreach($sections as $key => $section)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading{{ $key }}">
                            <button @class(['accordion-button ps-4', 'collapsed' => $key != $active['section']])
                                    type="button"
                                    wire:loading.attr="disabled"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse{{ $key }}"
                                    aria-expanded="false"
                                    aria-controls="collapse{{ $key }}">
                                {{ $section->name }}
                            </button>
                        </h2>
                        <div id="collapse{{ $key }}"
                             @class(['accordion-collapse collapse', 'show' => $key == $active['section']])
                             aria-labelledby="heading{{ $key }}"
                             data-bs-parent="#accordionExamSections">
                            <div class="accordion-body bg-body-light pt-0 px-0 pb-0">
                                <div class="list-group list-group-flush border border-top-0 border-danger"
                                     style="max-height: 540px; overflow-y: auto">
                                    @php $questionIndex = 1; @endphp
                                    @foreach($section->parents as $parentKey => $parent)
                                        <button
                                            wire:init="addSectionNavigation({{ $key }}, {{ $parent->id }})"
                                            wire:loading.attr="disabled"
                                            wire:click="setActive({{ $key }}, {{ $parent->id }})"
                                            @class([
                                                'list-group-item list-group-item-action',
                                                'd-flex align-items-center justify-content-between',
                                                'active' => $parent->id == $active['parent']
                                            ])>
                                            <div>

                                                @if(\App\Enums\TestSectionTypeEnum::QUESTION->is($parent->type))
                                                    <span>{{ __('Soru') }}</span>
                                                    <span>{{ $questionIndex }}</span>
                                                    @php $questionIndex++; @endphp
                                                @else
                                                    <span>{{ $parent->name }}</span>
                                                @endif
                                            </div>
                                            @if($this->countHistoryValue($parent->id) >= 4)
                                                <i class="fa fa-check mx-1 opacity-25"></i>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-lg-10 p-3 pt-2 exams-sections-right">
            <div class="progress mb-2 mt-0" id="exams-timer-progress" style="height: 6px" wire:ignore>
                <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: 100%"
                     aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <!-- wire:loading.remove -->
            <div class="w-100">
                @if(!empty($content))
                    @if(\App\Enums\TestSectionTypeEnum::CONTENT->is($content->type))
                        <div class="text-dark exams-sections-content">
                            {!! html_entity_decode($content->getMeta('content', '')) !!}
                        </div>
                    @endif

                    @if(\App\Enums\TestSectionTypeEnum::PDF->is($content->type))
                        <div class="text-dark exams-sections-file w-100">
                            <iframe style="min-height: 540px;"
                                    src="{{ asset($content->getMeta('meta_file', '')) }}#toolbar=1"
                                    class="w-100 border-0" allowfullscreen allowtransparency></iframe>
                        </div>
                    @endif

                    @if(\App\Enums\TestSectionTypeEnum::QUESTION->is($content->type))
                        <div class="text-dark exams-sections-question row">
                            @if($metaContent = $this->getParentContent($content->getMeta('questionParentId', 0)))
                                <div class="col-lg-6">
                                    @if(\App\Enums\TestSectionTypeEnum::CONTENT->is($metaContent->type))
                                        <div class="text-dark exams-sections-content p-1" style="max-height: 600px; overflow-y: auto; border: solid 1px #666; font-size: 20px;">
                                            {!! html_entity_decode($metaContent->getMeta('content', '')) !!}
                                        </div>
                                    @endif

                                    @if(\App\Enums\TestSectionTypeEnum::PDF->is($metaContent->type))
                                        <div class="text-dark exams-sections-file w-100 text-end">
                                            <a target="_blank" href="{{ $file = asset($metaContent->getMeta('meta_file', '')) }}" class="text-dark border px-2 py-1 mb-2">
                                                <i class="fa fa-external-link mx-1"></i>
                                                {{ __('Yeni Pencerede Görüntüle') }}
                                            </a>
                                            <iframe style="min-height: 540px;"
                                                    src="{{ $file }}#toolbar=1"
                                                    class="w-100 border-0" allowfullscreen allowtransparency></iframe>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div @class(['col-lg-12' => !$metaContent, 'col-lg-6' => $metaContent])>
                                @if($question = \App\Models\Question::find($content->getMeta('questionId', 0)))
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
                                            @foreach($question->answers as $key => $answer)
                                                <div class="col-3 col-sm-2 my-2">
                                                    <input type="radio"
                                                           class="btn-check"
                                                           id="answer{{ $answer->id }}"
                                                           wire:loading.attr="disabled"
                                                           wire:key="answer-{{ $active['section'] }}-{{ $active['parent'] }}-{{ $question->id }}"
                                                           wire:model.live="results.{{ $active['section'] }}.{{ $active['parent'] }}.{{ $question->id }}"
                                                           @if($this->isSelectedAnswer($question->id, $answer->id ))
                                                               wire:click="uncheckAnswer({{ $question->id }})"
                                                           @endif
                                                           autocomplete="off"
                                                           value="{{ $answer->id }}">
                                                    <label
                                                        class="label-check btn btn-outline-dark answer w-100 p-3 rounded-0 fw-bold text-nowrap"
                                                        wire:loading.attr="disabled"
                                                        style="font-size: 1.1rem"
                                                        for="answer{{ $answer->id }}">
                                                        <span>{{ $answer->title }}</span>
                                                    </label>
                                                </div>
                                            @endforeach

                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning p-3">
                                        <strong>{{ __('Gösterilecek soru bulunamadı!') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
            <!-- wire:loading.remove -->

        </div>
    </div>

    <div class="bg-light border-bottom border-top-0 exams-footer border-top py-0 row">
        <div class="col-lg-2 py-3 d-none d-sm-block">

        </div>
        <div class="col-lg-7 text-center text-sm-start ps-0 my-2 my-sm-0">
            <div class="btn-group" role="group">
                <button type="button"
                        class="btn btn-warning px-3 py-3 rounded-0 mx-1 d-flex align-items-center gap-1"
                        wire:click="prevSection"
                        wire:loading.attr="disabled" @disabled($this->prevIsDisabled())>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: 0.6s"></i>
                    </div>
                    <div wire:loading.remove>
                        <i class="fa fa-arrow-left mx-1 fa-faw"></i>
                    </div>
                    {{ __('Önceki') }}
                </button>
                <button type="button"
                        class="btn btn-warning px-3 py-3 rounded-0 mx-1 d-flex align-items-center gap-1"
                        wire:click="nextSection"
                        wire:loading.attr="disabled" @disabled($this->nextIsDisabled())>
                    {{ __('Sonraki') }}
                    <div wire:loading.remove>
                        <i class="fa fa-arrow-right mx-1 fa-faw"></i>
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: 0.6s"></i>
                    </div>
                </button>
            </div>
        </div>
        <div class="col-lg-3 text-end pe-3 mb-2 mb-sm-0">
            <button type="button"
                    class="btn w-100 btn-success px-4 py-3 rounded-0"
                    wire:click="save"
                    wire:confirm="{{ __('Sınav tamamlanacak ve sorulara verilen yanıtlar gönderilecektir, onaylıyor musunuz?') }}"
                    wire:loading.attr="disabled" @disabled($this->saveIsDisabled())>
                <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Sınavı Tamamla') }}
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: 0.6s"></i>
                </div>
            </button>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="finishedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
         aria-labelledby="finishedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finishedModalLabel">{{ __('Sınav Tamamlandı') }}</h5>
                    <button type="button" class="btn-close" wire:click="solvedExams" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-white px-3 py-5 text-center">
                    <div class="py-3">
                        @if(!empty($testResult))
                            @if($testResult->point >= $testResult->passing_score)
                                <i class="fa fa-check-double text-white fa-2x fw-bold mb-3 bg-success rounded-circle p-3"></i>
                                <h5 class="my-3 text-success">
                                    {{ __('Tebrikler, sınavı geçtin') }} 👏🏻
                                </h5>
                            @else
                                <i class="fa fa-circle-exclamation text-white fa-2x fw-bold mb-3 bg-danger rounded-circle p-3"></i>
                                <h5 class="my-3 text-danger">
                                    {{ __('Sınavı geçemedin, çalışmaya devam etmelisin!') }}
                                </h5>
                            @endif

                            <div class="text-center pt-3 mt-3 mb-3">
                                <span class="px-4 py-3 fs-5 bg-light fw-medium text-dark rounded-1 border border-secondary border-2">
                                    {{ __('Sınav Puanı') }}:
                                    <strong class="bg-dark rounded-2 text-white py-1 px-3">
                                        {{ $testResult->point }}
                                    </strong>
                                </span>
                            </div>
                        @endif

                        <a type="button" wire:click="solvedExams"
                           class="btn btn-success px-5 mt-3 fw-bold">
                            <i class="fa fa-poll mx-1"></i> {{ __('Sonuçları Görüntüle') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->

</div>
@push('script')
    <script>
        let timerText = $('#exams-timer-text'),
            timer = timerText.data('timer'),
            timerProgress = $('#exams-timer-progress').find('div.progress-bar'),
            dateOptions = {timeZone: '{{ settings()->timezone ?? config('app.timezone') }}'},
            dateNow = (new Date()).toLocaleString('en-US', dateOptions);

        Livewire.on('showFinishedModal', function () {
            let finishedModal = new bootstrap.Modal(document.getElementById('finishedModal'), {
                backdrop: 'static',
                keyboard: false,
                focus: false
            });
            finishedModal.show();
            $('#finishedModal').modal('show');
        });

        if (timerText.length) {
            let countDownDate = new Date(timer).getTime(),
                startDistance = countDownDate - (new Date(dateNow).getTime());
            let x = setInterval(function () {
                let now = new Date((new Date()).toLocaleString('en-US', dateOptions)).getTime();
                let distance = countDownDate - now;
                let percent = (distance / startDistance) * 100;

                timerProgress.css('width', percent + '%');
                timerProgress.attr('aria-valuenow', percent);

                let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                if (hours <= 9) hours = '0' + hours;

                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                if (minutes <= 9) minutes = '0' + minutes;

                let seconds = Math.floor((distance % (1000 * 60)) / 1000);
                if (seconds <= 9) seconds = '0' + seconds;

                timerText.text(hours + ':' + minutes + ':' + seconds);

                if (distance <= 30500) {
                    timerText.addClass('px-2 py-1 fw-bold bg-danger text-white rounded-2');
                    timerText.css('font-size', '20px');
                }

                if (distance <= 0) {
                    clearInterval(x);

                    timerText.text("{{ __('Sona Erdi/Bitti') }}");
                    setTimeout(() => {
                        let finishedModal = new bootstrap.Modal(document.getElementById('finishedModal'), {
                            backdrop: 'static',
                            keyboard: false,
                            focus: false
                        });
                        finishedModal.show();
                        $('#finishedModal').modal('show');
                    }, 250)

                    Livewire.dispatch('saveAndFinish');
                }
            }, 1000);
        }
    </script>
@endpush
