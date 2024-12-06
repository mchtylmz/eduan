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
            <div class="col-lg-3 mb-3">
                <label class="form-label" for="slug">{{ __('Slug (URL)') }}</label>
                <input type="text" class="form-control" id="slug" wire:model="slug"
                       placeholder="{{ __('Slug (URL)') }}.."/>
                <x-badge.error field="slug"/>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="sort">{{ __('Sıra') }}</label>
                <input type="number" min="1" class="form-control" id="sort" wire:model="sort"
                       placeholder="{{ __('Sıra') }}.."/>
                <x-badge.error field="sort"/>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="status">{{ __('Durum') }}</label>
                <select id="status" class="form-control" wire:model="status">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                    @endforeach
                </select>
                <x-badge.error field="status"/>
            </div>

            <div class="col-lg-12">
                <div class="row">
                    @foreach($languages = data()->languages(active: true) as $locale)
                        <div class="col-lg-6">
                            <div class="col-12 my-3 bg-body-light p-3">
                                <h5 class="mb-0">{{ str($locale->code)->upper() }} - {{ $locale->name }}</h5>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="title.{{ $locale->code }}">{{ __('Sayfa Başlığı') }} ({{ str($locale->code)->upper() }})</label>
                                <textarea rows="1"
                                          class="form-control"
                                          id="title.{{ $locale->code }}"
                                          @if($loop->index == 0) wire:model.live="title.{{ $locale->code }}" @endif
                                          @if($loop->index != 0) wire:model="title.{{ $locale->code }}" @endif
                                          placeholder="{{ __('Sayfa Başlığı') }}.."
                                ></textarea>
                                <x-badge.error field="title"/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="brief.{{ $locale->code }}">{{ __('Kısa Açıklama') }} ({{ str($locale->code)->upper() }})</label>
                                <textarea rows="2"
                                          class="form-control"
                                          id="brief.{{ $locale->code }}"
                                          wire:model="brief.{{ $locale->code }}"
                                          placeholder="{{ __('Kısa Açıklama') }}.."
                                ></textarea>
                                <x-badge.error field="brief"/>
                            </div>

                            <div class="row">
                                <div class="col-lg-2 mb-3">
                                    <label class="form-label">{{ __('Yönlendirme') }}</label>
                                    <div class="space-x-2">
                                        <div class="form-check form-switch form-check-inline">
                                            <input class="form-check-input" type="checkbox" value="{{ \App\Enums\YesNoEnum::YES->value }}" id="linkStatus" wire:model.live="linkStatus" name="linkStatus">
                                            <label class="form-check-label fw-semibold" for="linkStatus">
                                                @if(\App\Enums\YesNoEnum::YES->is($linkStatus))
                                                    <span class="text-success">{{ __('Açık') }}</span>
                                                @else
                                                    <span class="text-danger">{{ __('Kapalı') }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @if(\App\Enums\YesNoEnum::YES->is($linkStatus))
                                    <div class="col-lg-10 mb-3">
                                        <label class="form-label" for="link.{{ $locale->code }}">{{ __('Yönlendirme Linki') }}</label>
                                        <input type="text" class="form-control" id="link.{{ $locale->code }}" wire:model="link.{{ $locale->code }}"
                                               placeholder="{{ __('https...') }}.." />
                                        <x-badge.error field="link"/>
                                    </div>
                                @endif
                            </div>

                            <div class="mb-3" wire:ignore>
                                <label class="form-label" for="content.{{ $locale->code }}">{{ __('Sayfa İçeriği') }} ({{ str($locale->code)->upper() }})</label>
                                <x-tinymce.editor name="{{ $locale->code == 'tr' ? 'slug1':'slug2' }}" value="{!! html_entity_decode($content[$locale->code] ?? '') !!}"/>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="keywords.{{ $locale->code }}">{{ __('Anahtar Kelimeler') }} ({{ str($locale->code)->upper() }})</label>
                                <textarea rows="1"
                                          class="form-control"
                                          id="keywords.{{ $locale->code }}"
                                          wire:model="keywords.{{ $locale->code }}"
                                          placeholder="{{ __('Anahtar Kelimeler') }}.."
                                ></textarea>
                                <x-badge.error field="keywords"/>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

        </div>

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
