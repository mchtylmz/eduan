<div class="exam-workspace" x-data="examSession($wire)"
     data-started="{{ $testResult->created_at->getTimestampMs() }}"
     data-expires="{{ $testResult->expires_at->getTimestampMs() }}"
     data-server-now="{{ now()->getTimestampMs() }}"
     data-ended-text="{{ __('Sona Erdi/Bitti') }}">
    @php
        $content = $this->getContent();
        $labels = $this->sectionLabels;
        $answered = $this->resultQuestionsCount();
        $blank = max(0, $totalQuestionsCount - $answered);
    @endphp
    <div class="exam-toolbar">
        <div class="exam-heading">
            <h1>{{ $test->name }}</h1>
            @if($content)
                <p>{{ $sections->get($active['section'])->name }} <span aria-hidden="true">/</span> {{ $labels[$content->id] ?? $content->name }}</p>
            @endif
        </div>
        <div class="exam-summary">
            <div class="exam-answer-counter">
                <span>{{ __('Cevaplanan') }} <strong>{{ $answered }} / {{ $totalQuestionsCount }}</strong></span>
                <span>{{ __('Boş') }} <strong>{{ $blank }}</strong></span>
                <div class="progress" role="progressbar" aria-label="{{ __('Cevaplanan') }}"
                     aria-valuenow="{{ $answered }}" aria-valuemin="0" aria-valuemax="{{ $totalQuestionsCount }}">
                    <div class="progress-bar" style="width: {{ $this->resultQuestionsPercent() }}%"></div>
                </div>
            </div>
            <div class="exam-clock" :class="{ 'is-urgent': remaining <= 30 && !$wire.finished }" wire:ignore>
                <i class="fa fa-clock" aria-hidden="true"></i>
                <div><span>{{ __('Kalan Süre') }}</span><strong id="exams-timer-text" x-text="timeText">--:--:--</strong></div>
            </div>
        </div>
    </div>

    <div class="exam-feedback" role="status" aria-live="polite" aria-atomic="true" wire:ignore>
        <span x-show="!$wire.finished && !finishPending && !finishFailed && !saveFailed && online">
            <i class="fa" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'" aria-hidden="true"></i>
            <span x-show="saving">{{ __('Kaydediliyor') }}</span>
            <span x-show="!saving && saved">{{ __('Cevap kaydedildi') }}</span>
            <span x-show="!saving && !saved">{{ __('Cevaplar otomatik kaydedilir') }}</span>
        </span>
        <span x-show="!online" x-cloak class="text-danger">{{ __('Bağlantı yok. Süre işlemeye devam eder.') }}</span>
        <span x-show="saveFailed && online" x-cloak class="text-danger">
            {{ __('Cevap kaydedilemedi. Sayfayı yenileyip tekrar deneyin.') }}
            <button type="button" class="exam-text-button" @click="window.location.reload()">{{ __('Sayfayı Yenile') }}</button>
        </span>
        <span x-show="finishPending" x-cloak><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> {{ __('Sınav tamamlanıyor') }}</span>
        <span x-show="finishFailed && online" x-cloak class="text-danger">
            {{ __('Sınav tamamlanamadı. Kaydedilen cevaplar korunur.') }}
            <button type="button" class="exam-text-button" @click="finishAtDeadline(remaining <= 0)" :disabled="finishPending">{{ __('Tekrar Dene') }}</button>
        </span>
        <span x-show="$wire.finished" x-cloak>{{ __('Sınav Tamamlandı') }}</span>
    </div>

    <div class="exam-layout">
        <aside class="exam-sidebar">
            <button type="button" class="exam-menu-toggle" @click="menuOpen = !menuOpen"
                    :aria-expanded="menuOpen.toString()" aria-controls="exam-question-menu">
                <span><i class="fa fa-list" aria-hidden="true"></i> {{ __('Sınav bölümleri') }}</span>
                <i class="fa fa-chevron-down" :class="{ 'is-open': menuOpen }" aria-hidden="true"></i>
            </button>
            <nav id="exam-question-menu" class="exam-question-menu" :class="{ 'is-open': menuOpen }" aria-label="{{ __('Sınav bölümleri') }}">
                <div class="exam-menu-legend"><span><i class="fa fa-check-circle" aria-hidden="true"></i> {{ __('Cevaplandı') }}</span><span><i class="fa fa-circle" aria-hidden="true"></i> {{ __('Boş') }}</span></div>
                <div class="accordion" id="accordionExamSections">
                    @foreach($sections as $key => $section)
                        <div class="accordion-item" wire:key="exam-section-{{ $section->id }}">
                            <h2 class="accordion-header" id="exam-heading-{{ $section->id }}">
                                <button @class(['accordion-button', 'collapsed' => $key != $active['section']])
                                        type="button" data-bs-toggle="collapse" data-bs-target="#exam-section-{{ $section->id }}"
                                        aria-expanded="{{ $key == $active['section'] ? 'true' : 'false' }}" aria-controls="exam-section-{{ $section->id }}">
                                    {{ $section->name }}
                                </button>
                            </h2>
                            <div id="exam-section-{{ $section->id }}" @class(['accordion-collapse collapse', 'show' => $key == $active['section']])
                                 aria-labelledby="exam-heading-{{ $section->id }}" data-bs-parent="#accordionExamSections">
                                <div class="list-group list-group-flush exam-question-list">
                                    @foreach($section->parents as $parent)
                                        @php
                                            $isQuestion = \App\Enums\TestSectionTypeEnum::QUESTION->is($parent->type);
                                            $hasAnswer = $isQuestion && !empty($results[$key][$parent->id][$parent->question?->question_id ?? 0]);
                                            $visited = $this->countHistoryValue($parent->id) > 0;
                                        @endphp
                                        <button type="button" wire:key="exam-navigation-{{ $parent->id }}"
                                                wire:click="setActive({{ $key }}, {{ $parent->id }})" wire:loading.attr="disabled"
                                                @click="menuOpen = false" :disabled="busy || saving || saveFailed || !online || finishPending || remaining <= 0 || $wire.finished"
                                                data-section-id="{{ $parent->id }}" data-answer-state="{{ $hasAnswer ? 'answered' : 'blank' }}"
                                                aria-current="{{ $parent->id == $active['parent'] ? 'step' : 'false' }}"
                                                @class(['list-group-item list-group-item-action exam-question-link', 'active' => $parent->id == $active['parent'], 'is-answered' => $hasAnswer])>
                                            <span>{{ $labels[$parent->id] }}</span>
                                            @if($isQuestion)
                                                <i class="fa {{ $hasAnswer ? 'fa-check-circle' : 'fa-circle' }}" aria-hidden="true"></i>
                                                <span class="visually-hidden">{{ $hasAnswer ? __('Cevaplandı') : __('Boş') }}</span>
                                            @elseif($visited)
                                                <i class="fa fa-eye" aria-hidden="true"></i><span class="visually-hidden">{{ __('Görüntülendi') }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </nav>
        </aside>

        <div class="exam-main">
            <div class="exam-time-progress progress" id="exams-timer-progress" wire:ignore>
                <div class="progress-bar" role="progressbar" aria-label="{{ __('Kalan Süre') }}"
                     :style="'width: ' + percent + '%'" :aria-valuenow="Math.round(percent)" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="exam-content" :aria-busy="saving || finishPending">
                @if($content)
                    @if(\App\Enums\TestSectionTypeEnum::CONTENT->is($content->type))
                        <div class="exams-sections-content">{!! html_entity_decode($content->getMeta('content', '')) !!}</div>
                    @elseif(\App\Enums\TestSectionTypeEnum::PDF->is($content->type))
                        <iframe src="{{ asset($content->getMeta('meta_file', '')) }}#toolbar=1" class="exam-pdf" title="{{ $content->name ?: __('Sınav belgesi') }}"></iframe>
                    @elseif(\App\Enums\TestSectionTypeEnum::QUESTION->is($content->type))
                        <div class="row g-4">
                            @php($metaContent = $this->getParentContent($content->getMeta('questionParentId', 0)))
                            @if($metaContent)
                                <div class="col-lg-6">
                                    @if(\App\Enums\TestSectionTypeEnum::CONTENT->is($metaContent->type))
                                        <div class="exams-sections-content exam-related-content">{!! html_entity_decode($metaContent->getMeta('content', '')) !!}</div>
                                    @elseif(\App\Enums\TestSectionTypeEnum::PDF->is($metaContent->type))
                                        <a target="_blank" rel="noopener" href="{{ $file = asset($metaContent->getMeta('meta_file', '')) }}" class="exam-document-link">
                                            <i class="fa fa-external-link" aria-hidden="true"></i> {{ __('Yeni Pencerede Görüntüle') }}
                                        </a>
                                        <iframe src="{{ $file }}#toolbar=1" class="exam-pdf" title="{{ $metaContent->name ?: __('Sınav belgesi') }}"></iframe>
                                    @endif
                                </div>
                            @endif
                            <div @class(['col-lg-12' => !$metaContent, 'col-lg-6' => $metaContent])>
                                @if($question = \App\Models\Question::with('answers')->find($content->getMeta('questionId', 0)))
                                    <div class="exam-question-heading"><h2>{{ $labels[$content->id] }}</h2><span>{{ $sections->get($active['section'])->name }}</span></div>
                                    @if($question->title)<p class="exam-question-title">{{ $question->title }}</p>@endif
                                    @if($attachment = $question->attachment)
                                        <a class="exam-question-image" href="{{ getImage($attachment) }}" target="_blank" rel="noopener" title="{{ __('Görseli Büyüt') }}">
                                            <img src="{{ getImage($attachment) }}" alt="{{ $labels[$content->id] }}" loading="eager">
                                            <span><i class="fa fa-search-plus" aria-hidden="true"></i> {{ __('Görseli Büyüt') }}</span>
                                        </a>
                                    @endif
                                    <fieldset class="exam-answers">
                                        <legend>{{ __('Cevabınızı seçin') }}</legend>
                                        <div class="exam-answer-options">
                                            @foreach($question->answers as $answer)
                                                <div wire:key="exam-answer-{{ $content->id }}-{{ $answer->id }}">
                                                    <input type="radio" class="btn-check" id="exam-answer-{{ $answer->id }}"
                                                           name="exam-question-{{ $content->id }}" wire:loading.attr="disabled"
                                                           :disabled="busy || saving || saveFailed || !online || finishPending || remaining <= 0 || $wire.finished"
                                                           wire:model.live="results.{{ $active['section'] }}.{{ $active['parent'] }}.{{ $question->id }}"
                                                           @if($this->isSelectedAnswer($question->id, $answer->id)) wire:click="uncheckAnswer({{ $question->id }})" @endif
                                                           autocomplete="off" value="{{ $answer->id }}">
                                                    <label class="btn exam-answer" for="exam-answer-{{ $answer->id }}">{{ $answer->title }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="exam-answer-hint">{{ __('Seçili cevaba tekrar tıklayarak seçimi kaldırabilirsiniz.') }}</p>
                                    </fieldset>
                                @else
                                    <div class="alert alert-warning">{{ __('Gösterilecek soru bulunamadı!') }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="exam-actions">
        <div class="exam-navigation-actions">
            <button type="button" class="btn exam-nav-button" wire:click="prevSection" wire:loading.attr="disabled"
                    @disabled($this->prevIsDisabled() || $finished) :disabled="busy || saving || saveFailed || !online || finishPending || remaining <= 0 || $wire.finished || {{ $this->prevIsDisabled() ? 'true' : 'false' }}">
                <i class="fa fa-arrow-left" aria-hidden="true"></i> {{ __('Önceki') }}
            </button>
            <button type="button" class="btn exam-nav-button" wire:click="nextSection" wire:loading.attr="disabled"
                    @disabled($this->nextIsDisabled() || $finished) :disabled="busy || saving || saveFailed || !online || finishPending || remaining <= 0 || $wire.finished || {{ $this->nextIsDisabled() ? 'true' : 'false' }}">
                {{ __('Sonraki') }} <i class="fa fa-arrow-right" aria-hidden="true"></i>
            </button>
        </div>
        <button type="button" class="btn exam-finish-button" wire:click="save" wire:loading.attr="disabled"
                wire:confirm="{{ __('Cevaplanan') }}: {{ $answered }}/{{ $totalQuestionsCount }}. {{ __('Boş') }}: {{ $blank }}. {{ __('Sınav tamamlanacak ve sorulara verilen yanıtlar gönderilecektir, onaylıyor musunuz?') }}"
                @disabled($this->saveIsDisabled()) :disabled="busy || saving || saveFailed || !online || finishPending || remaining <= 0 || $wire.finished">
            <i class="fa fa-check" aria-hidden="true"></i> {{ __('Sınavı Tamamla') }}
        </button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="finishedModal" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
         aria-labelledby="finishedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finishedModalLabel">{{ __('Sınav Tamamlandı') }}</h5>
                    <button type="button" class="btn-close" wire:click="solvedExams" aria-label="{{ __('Kapat') }}"></button>
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

                        <button type="button" wire:click="solvedExams"
                           class="btn btn-success px-5 mt-3 fw-bold">
                            <i class="fa fa-poll mx-1"></i> {{ __('Sonuçları Görüntüle') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('script')
    <script src="{{ asset('assets/exam-session.js') }}?v={{ config('app.version') }}"></script>
@endpush
