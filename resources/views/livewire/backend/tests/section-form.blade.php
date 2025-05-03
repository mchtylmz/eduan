<div class="sections-body">

    <div class="row">
        <div class="col-lg-3 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('Dil') }}: <h5 class="mb-0">{{ $test->language->name ?? str($test->locale)->upper() }}</h5>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('Süre') }}:
                <h5 class="mb-0">
                    {{ $test->duration ?: 0 }} {{ __('saniye') }}
                    = {{ secondToTime($test->duration) }} {{ __('dakika') }}
                </h5>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('Sınav Adı') }}: <h5 class="mb-0">{{ $test->name }}</h5>
            </div>
        </div>
    </div>
    <hr>

    <div class="bg-light border border-info py-4 text-center mb-3">
        <button type="button" class="btn btn-alt-info px-4" wire:click="addSection" wire:loading.attr="disabled">
            <div wire:loading.remove>
                <i class="fa fa-plus mx-2 fa-faw"></i> {{ __('Yeni Bölüm Ekle') }}
            </div>
            <div wire:loading>
                <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
            </div>
        </button>
    </div>

    @if($errors->any())
        <div class="mb-3 mt-3">
            @foreach ($errors->all() as $error)
                <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
            @endforeach
        </div>
    @endif

    <form wire:submit="save" novalidate>

        <div wire:sortable="updateSectionsOrder"
             wire:sortable-group="updateSectionFieldsOrder"
             wire:sortable.options="{ animation: 100 }">
            @foreach($this->sections() as $index => $section)
                <!-- block -->
                <div class="block border border-info mb-4"
                     wire:key="sections-{{ $index }}" wire:sortable.item="{{ $index }}">
                    <!-- block-header -->
                    <div class="block-header block-header-default">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-1">
                                <i wire:sortable.handle class="fa fa-up-down-left-right me-1"></i>
                                <h3 class="block-title">
                                    {{ __('Sıra') }}: {{ $section['order'] }}
                                    {{ !empty($section['name']) ? ' - ' . $section['name'] : '' }}
                                </h3>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-alt-info px-2 py-1"
                                        wire:click="toggleSection({{ $index }})"
                                        wire:loading.attr="disabled">
                                    @if(isset($section['show']) && !$section['show'])
                                        <i class="fa fa-eye mx-2 fa-faw"></i> {{ __('Detayları Göster') }}
                                    @else
                                        <i class="fa fa-eye-slash mx-2 fa-faw"></i> {{ __('Detayları Gizle') }}
                                    @endif
                                </button>
                                <button type="button" class="btn btn-sm btn-alt-danger px-2 py-1"
                                        wire:confirm="{{ __('Bölüm sınavdan çıkarılacaktır, işleme devam edilsin mi?') }}"
                                        wire:click="deleteSection({{ $index }})"
                                        wire:loading.attr="disabled">
                                    <i class="fa fa-trash-alt mx-2 fa-faw"></i> {{ __('Kaldır') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @if(!empty($section['show']))
                        <!-- block-content -->
                        <div class="block-content fs-sm pb-3 ">
                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label"
                                           for="sections.{{ $index }}.name">{{ __('Bölüm Başlığı/Adı') }}</label>
                                    <input type="text" class="form-control" id="name"
                                           wire:model.change="sections.{{ $index }}.name"
                                           placeholder="{{ __('Adı') }}.." required/>
                                    <x-badge.error field="sections.{{ $index }}.name"/>
                                </div>
                            </div>

                            @if(!empty($section['fields']))
                                <hr>
                                <div wire:sortable-group.item-group="{{ $index }}">
                                    @php $fields = collect($section['fields'])->sortBy('order')->toArray(); @endphp
                                    @foreach($fields as $fieldIndex => $field)
                                        <div class="bg-light-subtle border border-warning p-0 mb-2" wire:key="sections-{{ $index }}-field-{{ $fieldIndex }}" wire:sortable-group.item="{{ $fieldIndex }}">
                                            <div
                                                class="bg-light p-2 px-3 d-flex align-items-center justify-content-between">
                                                <i wire:sortable-group.handle class="fa fa-up-down-left-right me-1"></i>
                                                <h3 class="block-title">
                                                    ({{ __('Sıra') }}: {{ $field['order'] }}
                                                    ) {{ $field['type']->name() }}
                                                </h3>
                                                <button type="button" class="btn btn-sm btn-alt-danger px-2 py-1"
                                                        wire:confirm="{{ __('İlgili alan çıkarılacaktır, işleme devam edilsin mi?') }}"
                                                        wire:click="deleteSectionField({{ $index }}, {{ $fieldIndex }})"
                                                        wire:loading.attr="disabled">
                                                    <i class="fa fa-trash-alt mx-2 fa-faw"></i> {{ __('Kaldır') }}
                                                </button>
                                            </div>
                                            <div class="p-3">

                                                @if(\App\Enums\TestSectionTypeEnum::QUESTION->isNot($field['type']))
                                                    <div class="row">
                                                        <div class="col-lg-6 mb-3">
                                                            <label class="form-label"
                                                                   for="fields.{{ $fieldIndex }}.name">{{ __('Alan Başlığı/Adı') }}</label>
                                                            <input type="text" class="form-control" id="name"
                                                                   wire:model="sections.{{ $index }}.fields.{{ $fieldIndex }}.name"
                                                                   placeholder="{{ __('Adı') }}.." required/>
                                                            <x-badge.error
                                                                field="sections.{{ $index }}.fields.{{ $fieldIndex }}.name"/>
                                                        </div>

                                                        @if(\App\Enums\TestSectionTypeEnum::PDF->is($field['type']))
                                                            <div class="col-lg-6 mb-3" wire:ignore>
                                                                <label class="form-label"
                                                                       for="fields.{{ $index }}.meta_file">
                                                                    {{ __('PDF Dosyası') }}
                                                                </label>
                                                                <div class="input-group mb-3">
                                                                    <input class="form-control" type="text"
                                                                           id="field.{{ $fieldIndex }}.meta_file"
                                                                           placeholder="{{ __('Dosya Yükle') }}"
                                                                           wire:model="sections.{{ $index }}.fields.{{ $fieldIndex }}.meta_file"
                                                                           readonly required>
                                                                    <button type="button" class="btn btn-info px-3"
                                                                            wire:click.prevent="showSelectPdfModal({{ $index }}, {{ $fieldIndex }})"
                                                                            wire:loading.attr="disabled">
                                                                        <i class="fa fa-upload fa-faw"></i>
                                                                        {{ __('Yükle') }}
                                                                    </button>
                                                                </div>
                                                                <x-badge.error
                                                                    field="fields.{{ $fieldIndex }}.meta_file"/>
                                                            </div>
                                                        @endif

                                                        @if(\App\Enums\TestSectionTypeEnum::CONTENT->is($field['type']))
                                                            <div class="col-lg-6 mb-3" wire:ignore>
                                                                <label class="form-label"
                                                                       for="fields.{{ $index }}.meta_content">
                                                                    {{ __('İçerik Metni') }}
                                                                </label>
                                                                <div class="input-group mb-3">
                                                                    <input class="form-control" type="text"
                                                                           id="field.{{ $fieldIndex }}.meta_content"
                                                                           placeholder="{{ __('İçerik metni ekle/düzenle') }}"
                                                                           wire:click.prevent="showSelectContentModal({{ $index }}, {{ $fieldIndex }})"
                                                                           wire:model="sections.{{ $index }}.fields.{{ $fieldIndex }}.meta_content"
                                                                           readonly required>
                                                                    <button type="button" class="btn btn-info px-3"
                                                                            wire:click.prevent="showSelectContentModal({{ $index }}, {{ $fieldIndex }})"
                                                                            wire:loading.attr="disabled">
                                                                        <i class="fa fa-pen fa-faw"></i>
                                                                        {{ __('İçeriği Düzenle') }}
                                                                    </button>
                                                                </div>
                                                                <x-badge.error
                                                                    field="sections.{{ $index }}.fields.{{ $fieldIndex }}.meta_content"/>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if(\App\Enums\TestSectionTypeEnum::QUESTION->is($field['type']))
                                                    <div
                                                        class="d-flex align-items-center justify-content-between p-3 text-center">
                                                        <div
                                                            class="row align-items-center justify-content-between w-100">
                                                            <div class="col-lg-9">
                                                                <x-badge.error
                                                                    field="sections.{{ $index }}.fields.{{ $fieldIndex }}.questionId"/>

                                                                @if(!empty($field['questionId']) && $field['question']->exists)
                                                                    <div class="row">
                                                                        <div class="col-lg-6">
                                                                            <img
                                                                                src="{{ asset($field['question']->attachment) }}"
                                                                                class="card-img-top"
                                                                                style="width: 100%; max-height: 180px; object-fit: contain"
                                                                                alt="question {{ $field['questionId'] }}">
                                                                        </div>
                                                                        <div class="col-lg-4">
                                                                            @foreach($field['question']->answers as $answer)
                                                                                <div class="block block-rounded mb-1">
                                                                                    <div
                                                                                        class="block-content block-content-full border border-2 ribbon ribbon-modern ribbon-glass p-2 d-flex align-items-center justify-content-start">
                                                                                        @if(\App\Enums\YesNoEnum::YES->is($answer->correct))
                                                                                            <div
                                                                                                class="ribbon-box bg-success">
                                                                                                <i class="fa fa-check mx-1"></i>
                                                                                            </div>
                                                                                        @endif
                                                                                        <div class="text-center">
                                                                                    <span
                                                                                        class="px-3">{{ $answer->title }}</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                        <div class="col-lg-6 mb-3 text-start">
                                                                            <label class="form-label" for="sections.{{ $index }}.fields.{{ $fieldIndex }}.questionParentId">
                                                                                {{ __('Bağlı Alan') }}
                                                                            </label>
                                                                            <select id="sections.{{ $index }}.fields.{{ $fieldIndex }}.questionParentId"
                                                                                    class="form-control"
                                                                                    wire:model="sections.{{ $index }}.fields.{{ $fieldIndex }}.questionParentId">
                                                                                <option value="">{{ __('Seçiniz') }}</option>
                                                                                @foreach($fields as $optionFieldIndex => $optionField)
                                                                                    @if(\App\Enums\TestSectionTypeEnum::QUESTION->isNot($optionField['type']))
                                                                                        <option value="{{ $optionFieldIndex }}" @selected(($field['questionParentId'] ?? false) == $optionFieldIndex)>
                                                                                            ({{ __('Sıra') }}: {{ $optionField['order'] }}
                                                                                            ) {{ $optionField['type']->name() }}
                                                                                        </option>
                                                                                    @endif
                                                                                @endforeach
                                                                            </select>
                                                                            <x-badge.error field="status"/>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="col-lg-3">
                                                                <button type="button" class="btn w-100 btn-warning px-3"
                                                                        wire:click.prevent="showSelectQuestionModal({{ $index }}, {{ $fieldIndex }})"
                                                                        wire:loading.attr="disabled">
                                                                    <i class="fa fa-question-circle mx-2 fa-faw"></i>
                                                                    {{ __('Soru Seç') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            @endif

                            <hr>
                            <div class="bg-light border border-warning py-4 text-center">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-warning px-4"
                                            wire:click="addSectionField({{ $index }}, 'content')"
                                            wire:loading.class="d-none">
                                        <i class="fa fa-file-word mx-2 fa-faw"></i> {{ __('İçerik Ekle') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-warning px-4"
                                            wire:click="addSectionField({{ $index }}, 'pdf')"
                                            wire:loading.class="d-none">
                                        <i class="fa fa-file mx-2 fa-faw"></i> {{ __('PDF Ekle') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-warning px-4"
                                            wire:click="addSectionField({{ $index }}, 'question')"
                                            wire:loading.class="d-none">
                                        <i class="fa fa-question mx-2 fa-faw"></i> {{ __('Soru Ekle') }}
                                    </button>
                                    <div wire:loading>
                                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- block-content -->
                    @endif
                </div>
                <!-- block -->
            @endforeach
        </div>

        @if(auth()->user()->can('tests:update') && !empty($sections))
            <div class="mb-3 text-center py-2">
                <button type="submit" class="btn btn-alt-success px-5" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Bölümleri Kaydet') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                    </div>
                </button>
            </div>
        @endif
    </form>

</div>
@push('script')
    <script src="https://unpkg.com/@wotz/livewire-sortablejs@1.0.0/dist/livewire-sortable.js"></script>
@endpush
