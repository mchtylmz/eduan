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

            <div class="col-lg-6 mb-3">
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

        </div>

        <ul class="nav nav-tabs nav-tabs-alt bg-body-light" role="tablist">
            @foreach($languages = data()->languages(active: true) as $language)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ $loop->index == 0 ? 'active': '' }}"
                            id="page-tab-{{ $language->code }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#page-tab-{{ $language->code }}"
                            role="tab"
                            aria-controls="page-tab-{{ $language->code }}"
                            aria-selected="{{ $loop->index ? 'true': 'false' }}">
                        {{ str($language->code)->upper() }} - {{ $language->name }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="block-content tab-content">
            @foreach($languages = data()->languages(active: true) as $language)
                <div class="tab-pane row py-1 {{ $loop->index == 0 ? 'active show': '' }}"
                     id="page-tab-{{ $language->code }}"
                     role="tabpanel"
                     aria-labelledby="page-tab-{{ $language->code }}"
                     tabindex="0">

                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label class="form-label" for="title_{{ $language->code }}">{{ __('Başlık') }}</label>
                            <textarea rows="1"
                                      class="form-control"
                                      id="title_{{ $language->code }}"
                                      @if($language->code == settings()->defaultLocale)
                                          wire:model.live="title.{{ $language->code }}"
                                      @else
                                          wire:model="title.{{ $language->code }}"
                                      @endif
                                      placeholder="{{ __('Başlık') }}.."
                            ></textarea>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label class="form-label" for="brief_{{ $language->code }}">{{ __('Kısa Açıklama') }}</label>
                            <textarea rows="2"
                                      class="form-control"
                                      id="brief_{{ $language->code }}"
                                      wire:model="brief.{{ $language->code }}"
                                      placeholder="{{ __('Kısa Açıklama') }}.."
                            ></textarea>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label class="form-label" for="keywords_{{ $language->code }}">{{ __('Anahtar Kelimeler') }}</label>
                            <textarea rows="2"
                                      class="form-control"
                                      id="keywords_{{ $language->code }}"
                                      wire:model="keywords.{{ $language->code }}"
                                      placeholder="{{ __('Anahtar Kelimeler') }}.."
                            ></textarea>
                        </div>


                        <div class="col-lg-12 my-3">
                            <div class="bg-body-light p-2 px-3">
                                <h5 class="mb-0">{{ __('Karşılama Alanı') }}</h5>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.welcomeTitle">
                                    {{ __('Başlık') }}
                                </label>
                                <textarea rows="1"
                                          class="form-control"
                                          id="content.{{ $language->code }}.welcomeTitle"
                                          wire:model="content.{{ $language->code }}.welcomeTitle"
                                          placeholder="{{ __('Başlık') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.welcomeDescription">
                                    {{ __('Kısa Açıklama') }}
                                </label>
                                <textarea rows="2"
                                          class="form-control"
                                          id="content.{{ $language->code }}.welcomeDescription"
                                          wire:model="content.{{ $language->code }}.welcomeDescription"
                                          placeholder="{{ __('Açıklama') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.search">{{ __('Arama Kutusu Durumu') }}</label>
                                <select id="content.{{ $language->code }}.search" class="form-control" wire:model="content.{{ $language->code }}.search">
                                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-5 mb-3" wire:ignore>
                            <label class="form-label" for="content.{{ $language->code }}.image">{{ __('Görsel') }}</label>
                            <input type="file" class="dropify" id="content.{{ $language->code }}.image"
                                   wire:model="content.{{ $language->code }}.image"
                                   data-show-remove="false"
                                   data-show-errors="true"
                                   data-allowed-file-extensions="jpg png jpeg webp"
                                   accept=".jpg,.png,.jpeg,.webp"
                                   data-max-file-size="10M"
                                   @if(!empty($content[$language->code]['image']))
                                       data-default-file="{{ asset($content[$language->code]['image']) }}"
                                   @endif
                            />
                        </div>

                        <div class="col-lg-12 my-3">
                            <div class="bg-body-light p-2 px-3">
                                <h5 class="mb-0">{{ __('Dersler') }}</h5>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.lessonTitle">
                                    {{ __('Başlık') }}
                                </label>
                                <textarea rows="1"
                                          class="form-control"
                                          id="content.{{ $language->code }}.lessonTitle"
                                          wire:model="content.{{ $language->code }}.lessonTitle"
                                          placeholder="{{ __('Başlık') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.lessonDescription">
                                    {{ __('Kısa Açıklama') }}
                                </label>
                                <textarea rows="2"
                                          class="form-control"
                                          id="content.{{ $language->code }}.lessonDescription"
                                          wire:model="content.{{ $language->code }}.lessonDescription"
                                          placeholder="{{ __('Açıklama') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="lessonCount">{{ __('Gösterilecek Ders Sayısı') }}</label>
                                <input type="number" min="1" class="form-control" id="lessonCount" wire:model="content.{{ $language->code }}.lessonCount" placeholder="{{ __('Sayı') }}.." />
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.lessonStatus">{{ __('Durum') }}</label>
                                <select id="content.{{ $language->code }}.lessonStatus" class="form-control" wire:model="content.{{ $language->code }}.lessonStatus">
                                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-12 my-3">
                            <div class="bg-body-light p-2 px-3">
                                <h5 class="mb-0">{{ __('Testler') }}</h5>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.testTitle">
                                    {{ __('Başlık') }}
                                </label>
                                <textarea rows="1"
                                          class="form-control"
                                          id="content.{{ $language->code }}.testTitle"
                                          wire:model="content.{{ $language->code }}.testTitle"
                                          placeholder="{{ __('Başlık') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.testDescription">
                                    {{ __('Kısa Açıklama') }}
                                </label>
                                <textarea rows="2"
                                          class="form-control"
                                          id="content.{{ $language->code }}.testDescription"
                                          wire:model="content.{{ $language->code }}.testDescription"
                                          placeholder="{{ __('Açıklama') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="testCount">{{ __('Gösterilecek Test Sayısı') }}</label>
                                <input type="number" min="1" class="form-control" id="testCount" wire:model="content.{{ $language->code }}.testCount" placeholder="{{ __('Sayı') }}.." />
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.testStatus">{{ __('Durum') }}</label>
                                <select id="content.{{ $language->code }}.testStatus" class="form-control" wire:model="content.{{ $language->code }}.testStatus">
                                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-12 my-3">
                            <div class="bg-body-light p-2 px-3">
                                <h5 class="mb-0">{{ __('Sıkça Sorulan Sorular') }}</h5>
                            </div>
                        </div>

                        <div class="col-lg-5 mb-3" wire:ignore>
                            <label class="form-label" for="content.{{ $language->code }}.faqImage">{{ __('Sol Görsel') }}</label>
                            <input type="file" class="dropify" id="content.{{ $language->code }}.faqImage"
                                   wire:model="content.{{ $language->code }}.faqImage"
                                   data-show-remove="false"
                                   data-show-errors="true"
                                   data-allowed-file-extensions="jpg png jpeg webp"
                                   accept=".jpg,.png,.jpeg,.webp"
                                   data-max-file-size="10M"
                                   @if(!empty($content[$language->code]['faqImage']))
                                       data-default-file="{{ asset($content[$language->code]['faqImage']) }}"
                                @endif
                            />
                        </div>
                        <div class="col-lg-7">
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.faqTitle">
                                    {{ __('Başlık') }}
                                </label>
                                <textarea rows="1"
                                          class="form-control"
                                          id="content.{{ $language->code }}.faqTitle"
                                          wire:model="content.{{ $language->code }}.faqTitle"
                                          placeholder="{{ __('Başlık') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.faqDescription">
                                    {{ __('Kısa Açıklama') }}
                                </label>
                                <textarea rows="2"
                                          class="form-control"
                                          id="content.{{ $language->code }}.faqDescription"
                                          wire:model="content.{{ $language->code }}.faqDescription"
                                          placeholder="{{ __('Açıklama') }}.."
                                ></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="faqCount">{{ __('Gösterilecek Soru Sayısı') }}</label>
                                <input type="number" min="1" class="form-control" id="faqCount" wire:model="content.{{ $language->code }}.faqCount" placeholder="{{ __('Sayı') }}.." />
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="content.{{ $language->code }}.faqStatus">{{ __('Durum') }}</label>
                                <select id="content.{{ $language->code }}.faqStatus" class="form-control" wire:model="content.{{ $language->code }}.faqStatus">
                                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                                    @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                                        <option value="{{ $optionKey }}">{{ $optionText }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- row -->
                </div>
            @endforeach
        </div>

        @can($permission)
            <div class="mb-3 text-center py-2 mt-3">
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
