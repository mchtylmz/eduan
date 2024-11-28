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
                <label class="form-label" for="sort">{{ __('Sıra') }}</label>
                <input type="number" min="1" class="form-control" id="sort" wire:model="sort"
                       placeholder="{{ __('Sıra') }}.."/>
                <x-badge.error field="sort"/>
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

            <div class="col-lg-12 mb-3">
                <label class="form-label" for="title">{{ __('Soru Başlığı') }}</label>
                <textarea rows="1"
                          class="form-control"
                          id="title"
                          wire:model="title"
                          placeholder="{{ __('Soru Başlığı') }}.."
                ></textarea>
            </div>
            <div class="col-lg-12 mb-3" wire:ignore>
                <label class="form-label" for="content">{{ __('Açıklama') }}</label>
                <x-tinymce.editor name="content" value="{{ $content }}"/>
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
