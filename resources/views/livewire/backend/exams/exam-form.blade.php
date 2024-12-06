<div>

    @if($errors->any())
        <div class="mb-3">
            @foreach ($errors->all() as $error)
                <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
            @endforeach
        </div>
    @endif

    <form wire:submit.prevent="save" novalidate>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <label class="form-label" for="name">{{ __('Test Adı') }}</label>
                <textarea
                    rows="1"
                    class="form-control"
                    id="name"
                    wire:model="name"
                    placeholder="{{ __('Test Adı') }}.."
                ></textarea>
                <x-badge.error field="name"/>
            </div>

            <div class="col-lg-12 mb-3" wire:ignore>
                <label class="form-label" for="content">{{ __('Açıklama') }}</label>
                <x-tinymce.editor name="content" value="{{ $content }}" height="240" />
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="code">{{ __('Kodu') }}</label>
                <input type="text" class="form-control" id="code" wire:model="code"
                       placeholder="{{ __('Kodu') }}.."/>
                <x-badge.error field="code"/>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="visibility">{{ __('Test Çözülebilme Durumu') }}</label>
                <select id="visibility" class="form-control" wire:model="visibility">
                    <option value="">{{ __('Seçiniz') }}</option>
                    @foreach(\App\Enums\VisibilityEnum::options() as $optionKey => $optionText)
                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                    @endforeach
                </select>
                <x-badge.error field="visibility"/>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="locale">{{ __('Dil') }}</label>
                <select id="locale" class="form-control" wire:model="locale">
                    @foreach(data()->languages(active: !$locale) as $language)
                        <option value="{{ $language->code }}" @selected($language->code == $locale)>{{ str($language->code)->upper() }} - {{ $language->name }}</option>
                    @endforeach
                </select>
                <x-badge.error field="status"/>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="status">{{ __('Durum') }}</label>
                <select id="status" class="form-control" wire:model="status">
                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                    @endforeach
                </select>
                <x-badge.error field="status"/>
            </div>

        </div>

        <!-- block -->
        <div class="block mt-3 mb-0">
            <!-- block-header -->
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('Soru Seçimi') }}</h3>
            </div>
            <!-- block-content -->
            <div class="block-content fs-sm pb-3">
                <x-badge.error field="examQuestions" class="mb-3 bg-danger text-white"/>

                <div class="row">
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
                                    <h5 class="fs-base fw-semibold text-dark mb-0 py-2 px-3">
                                        {{ __('Kodu') }}: {{ $question->code }}
                                    </h5>
                                    <div class="block-content p-0 bg-body-light">
                                        <img class="w-100 object-fit-contain"
                                             style="height: 200px;"
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
                                                            <div class="block-content block-content-full border border-2 ribbon ribbon-modern ribbon-glass p-2 d-flex align-items-center justify-content-start">
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
                                        @if(!empty($examQuestions[$question->id]))
                                            <button type="button"
                                                    class="btn btn-sm btn-danger w-100 h-100"
                                                    wire:click="toggleQuestions({{ $question->id }})"
                                                    wire:loading.attr="disabled">
                                                <div wire:loading.remove>
                                                    <i class="fa fa-fw fa-close mx-1"></i> {{ __('Çıkar') }}
                                                </div>
                                                <div wire:loading>
                                                    <i class="fa fa-fw fa-spinner fa-pulse"
                                                       style="animation-duration: 0.6s"></i>
                                                </div>
                                            </button>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm btn-success w-100 h-100"
                                                    wire:click="toggleQuestions({{ $question->id }})"
                                                    wire:loading.attr="disabled">
                                                <div wire:loading.remove>
                                                    <i class="fa fa-fw fa-check mx-1"></i> {{ __('Teste Ekle') }}
                                                </div>
                                                <div wire:loading>
                                                    <i class="fa fa-fw fa-spinner fa-pulse"
                                                       style="animation-duration: 0.6s"></i>
                                                </div>
                                            </button>
                                        @endif
                                        TODO: // kaç teste eklendi yazacak
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
            <!-- block-content -->
        </div>
        <!-- block -->

        @can($permission)
            <div class="mb-3 mt-0 text-center py-2">
                <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Testi Kaydet') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                    </div>
                </button>
            </div>
        @endcan
        <hr>

        @if(count($examQuestions = $this->examQuestions()))
            <div class="bg-body-light mb-1 p-3 text-center">
                <h5 class="mb-0">{{ __('Seçilen Sorular') }}</h5>
                <p class="mb-0 fw-medium">
                    @php
                        $statistic = $this->examQuestionsStatistic();
                    @endphp
                    <span class="me-1">{{ $statistic['count'] }} {{ __('soru') }},</span>
                    <span class="me-1">{{ $statistic['sumTime'] }} {{ __('saniye') }},</span>
                    <span>∼ {{ $statistic['sumMinute'] }} {{ __('dakika') }}</span>
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                    <tr>
                        <th scope="col" class="w-10 text-center"><i class="fa fa-arrows-alt"></i> {{ __('Sıra') }}</th>
                        <th scope="col" class="w-20 text-center">{{ __('Ders') }}</th>
                        <th scope="col" class="w-20">{{ __('Konu') }}</th>
                        <th scope="col">{{ __('Soru') }}</th>
                        <th scope="col" class="w-10 text-center">{{ __('Süre') }}</th>
                        <th scope="col" class="w-20 text-center">{{ __('İşlem') }}</th>
                    </tr>
                    </thead>
                    <tbody wire:sortable="updateExamQuestionsOrder" wire:sortable.options="{ animation: 100 }">
                    @foreach($examQuestions as $key => $examQuestion)
                        @php $examQuestion = $examQuestion['question']; @endphp
                        <tr wire:sortable.item="{{ $examQuestion->id }}" wire:key="examQuestion-{{ $key }}">
                            <td class="text-center" style="cursor: pointer">
                                <i class="fa fa-arrows-alt"></i> {{ $loop->iteration }}.
                            </td>
                            <td>
                                {{ $examQuestion->lesson->name }}
                            </td>
                            <td>
                                {{ $examQuestion->topic->title }}
                            </td>
                            <td>
                                <p class="mb-0">{{ $examQuestion->title }}</p>
                                @if($examQuestion->attachment)
                                    <img src="{{ asset($examQuestion->attachment_url) }}"
                                         alt="{{ $examQuestion->id }}"
                                         class="img-thumbnail"
                                         style="max-height: 120px; object-fit: contain"/>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $examQuestion->time }} {{ __('sn') }}
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-danger w-100"
                                        wire:confirm="{{ __('Soru testten çıkarılacaktır, işleme devam edilsin mi?') }}"
                                        wire:click="toggleQuestions({{ $examQuestion->id }})"
                                        wire:loading.attr="disabled">
                                    <div wire:loading.remove>
                                        <i class="fa fa-fw fa-close mx-1"></i> {{ __('Çıkar') }}
                                    </div>
                                    <div wire:loading>
                                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                                    </div>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @can($permission)
                <div class="mb-3 mt-0 text-center py-2">
                    <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Testi Kaydet') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                        </div>
                    </button>
                </div>
            @endcan
        @endif

    </form>

</div>
@push('script')
    <script src="https://unpkg.com/@wotz/livewire-sortablejs@1.0.0/dist/livewire-sortable.js"></script>
@endpush
