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
            <div class="col-lg-6 mb-3" wire:ignore>
                <label class="form-label" for="lesson_id">{{ __('Ders') }}</label>
                <select id="lesson_id" class="form-control selectpicker" data-live-search="true" data-size="6" wire:model.live="lesson_id" data-header="{{ count($this->lessons()) }} {{ __('Ders') }}">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach($this->lessons() as $lesson)
                        <option value="{{ $lesson->id }}" @selected($lesson->id == $lesson_id)>{{ $lesson->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6 mb-3">
                <label class="form-label" for="topic_id">{{ __('Konu') }}</label>
                <select id="topic_id" class="form-control selectpicker" data-live-search="true" data-size="6" wire:model.live="topic_id" data-header="{{ count($topics ?? []) }} {{ __('Konu') }}">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @if($topics)
                        @foreach($topics as $topic)
                            <option value="{{ $topic->id }}" @selected($topic->id == $topic_id)>{{ $topic->display_title }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="locale">{{ __('Dil') }}</label>
                <select id="locale" class="form-control" wire:model="locale">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach(data()->languages(active: !$locale) as $language)
                        <option value="{{ $language->code }}" @selected($language->code == $locale)>{{ str($language->code)->upper() }} - {{ $language->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="code">{{ __('Kodu') }}</label>
                <input type="text" class="form-control" id="code" wire:model="code"
                       placeholder="{{ __('Kodu') }}.."/>
                <x-badge.error field="code"/>
            </div>

            <div class="col-lg-2 mb-3">
                <label class="form-label" for="time">{{ __('Süre') }}</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="time" wire:model="time">
                    <span class="input-group-text">{{ __('sn') }}</span>
                </div>
                <x-badge.error field="time" />
            </div>

            <div class="col-lg-2 mb-3">
                <label class="form-label" for="sort">{{ __('Sıra') }}</label>
                <input type="number" min="1" class="form-control" id="sort" wire:model="sort"
                       placeholder="{{ __('Sıra') }}.."/>
                <x-badge.error field="sort"/>
            </div>

            <div class="col-lg-2 mb-3">
                <label class="form-label" for="status">{{ __('Durum') }}</label>
                <select id="status" class="form-control" wire:model="status">
                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                    @endforeach
                </select>
                <x-badge.error field="status"/>
            </div>

            <div class="col-lg-12"><hr></div>

            <div class="col-lg-6 mb-3" wire:ignore>
                <label class="form-label" for="attachment">{{ __('Soru Resmi') }}</label>
                <input type="file" class="dropify" id="attachment" wire:model.live="attachment"
                       data-show-remove="false"
                       data-show-errors="true"
                       data-allowed-file-extensions="jpg png jpeg webp"
                       data-max-file-size="3M"
                       @if(!empty($question->attachment)) data-default-file="{{ asset($question->attachment) }}" @endif
                />
                <x-badge.error field="attachment" />
            </div>

            <div class="col-lg-6 mb-3" wire:ignore>
                <label class="form-label" for="solution">{{ __('Soru Çözümü') }}</label>
                <input type="file" class="dropify" id="solution" wire:model.live="solution"
                       data-show-remove="false"
                       data-show-errors="true"
                       data-allowed-file-extensions="jpg png jpeg webp"
                       data-max-file-size="3M"
                       @if(!empty($question->solution)) data-default-file="{{ asset($question->solution) }}" @endif
                />
                <x-badge.error field="solution" />
            </div>

        </div>

        <!-- block -->
        <div class="block my-3">
            <!-- block-header -->
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ __('Yanıtlar') }}</h3>

                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-success" wire:click.prevent="answerAdd">
                        <i class="fa fa-fw fa-plus mx-1"></i> {{ __('Yeni Yanıt Ekle') }}
                    </button>
                </div>
            </div>
            <!-- block-content -->
            <div class="block-content fs-sm pb-3">
                <x-badge.error field="answers" class="mb-3 text-white bg-danger"/>

                <div class="row">
                    <!-- answer template -->
                    @foreach($answers as $index => $answer)
                        <div class="col-lg-4 px-3">
                            <div class="input-group mb-3">
                                <span class="input-group-text">{!! letters($index) !!}</span>
                                <input type="text"
                                       class="form-control"
                                       placeholder="{!! letters($index) !!}"
                                       wire:model="answers.{{ $index }}.title">
                                <div class="input-group-text pe-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               role="switch"
                                               value="{{ \App\Enums\YesNoEnum::YES }}"
                                               id="correct_{{ $index }}"
                                               name="answers.{{ $index }}.correct"
                                               wire:model.live="answers.{{ $index }}.correct"
                                            @checked(App\Enums\YesNoEnum::YES->is($answer['correct']))>
                                        <label class="form-check-label">
                                            @if(App\Enums\YesNoEnum::YES->is($answer['correct']))
                                                {{ __('Doğru') }}
                                            @else
                                                {{ __('Yanlış') }}
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                <button class="btn btn-danger"
                                        type="button"
                                        wire:click.prevent="answerRemove({{ $index }})">
                                    <i class="fa fa-trash-alt mx-1"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                    <!-- answer template -->
                </div>

                <x-badge.error field="answers" class="mt-3 text-white bg-danger"/>
            </div>
            <!-- block-content -->
        </div>
        <!-- block -->

        @can($permission)
            <div class="mb-3 text-center py-2">
                <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                    </div>
                </button>
            </div>
        @endcan
    </form>

</div>
