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
                <label class="form-label" for="locale">{{ __('Dil') }}</label>
                <select id="locale" class="form-control" wire:model="locale">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach(data()->languages(active: !$locale) as $language)
                        <option value="{{ $language->code }}" @selected($language->code == $locale)>{{ str($language->code)->upper() }} - {{ $language->name }}</option>
                    @endforeach
                </select>
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
            <div class="col-lg-3 mb-3" wire:ignore>
                <label class="form-label" for="published_at">{{ __('Paylaşım Zamanı') }}</label>
                <input type="text" class="js-flatpickr form-control" id="published_at" wire:model="published_at" data-enable-time="true" data-time_24hr="true" data-locale="{{ $locale }}" value="{{ $published_at }}">
            </div>

            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label" for="title">{{ __('Blog Başlığı') }}</label>
                    <textarea rows="2"
                              class="form-control"
                              id="title"
                              wire:model.live="title"
                              placeholder="{{ __('Blog Başlığı') }}.."
                    ></textarea>
                    @if($slug)
                        <div class="w-100 bg-body-light py-1 px-2">{{ __('Url') }}: {{ $slug }}</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label" for="brief">{{ __('Kısa Açıklama') }}</label>
                    <textarea rows="3"
                              class="form-control"
                              id="brief"
                              wire:model="brief"
                              placeholder="{{ __('Kısa Açıklama') }}.."
                    ></textarea>
                </div>
            </div>

            <div class="col-lg-6 mb-3" wire:ignore>
                <label class="form-label" for="image">{{ __('Blog Görseli') }}</label>
                <input type="file" class="dropify" id="image" wire:model="image"
                       data-show-remove="false"
                       data-show-errors="true"
                       data-allowed-file-extensions="jpg png jpeg webp"
                       accept=".jpg,.jpeg,.png,.webp"
                       data-max-file-size="10M"
                       @if(!empty($blog->image)) data-default-file="{{ getImage($blog->image) }}" @endif
                />
            </div>

            <div class="col-lg-12 mb-3" wire:ignore>
                <label class="form-label" for="content">{{ __('İçerik') }}</label>
                <x-tinymce.editor name="content" value="{{ $content }}"/>
            </div>

            <div class="col-lg-12 mb-3">
                <label class="form-label" for="keywords">{{ __('Anahtar Kelimeler') }}</label>
                <textarea rows="1"
                          class="form-control"
                          id="keywords"
                          wire:model="keywords"
                          placeholder="{{ __('Anahtar Kelimeler') }}.."
                ></textarea>
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
