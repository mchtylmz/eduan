<div>
    <form wire:submit.prevent="save" novalidate>

        <div class="bg-body-light py-2 mb-3">
            <div class="row align-items-center justify-content-between">
                <div class="col-lg-6">
                    <p class="m-0 px-3 fs-6">
                        {{ __('Orjinal Metin') }}: <strong>{{ $this->translationsCount() }}</strong> /
                        {{ __('Çeviri Metin') }}: <strong>{{ $this->translatedCount() }}</strong>
                    </p>
                </div>
                <div class="col-lg-3">
                    @can($permission)
                        <button type="submit" class="btn btn-alt-success px-4 w-100 didi" wire:loading.attr="disabled">
                            <div wire:loading.remove>
                                <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Tüm Çevirileri Kaydet') }}
                            </div>
                            <div wire:loading>
                                <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                            </div>
                        </button>
                        @if($fileTime)
                            <p class="text-end mb-0 mt-1 px-3 fs-xs">{{ __('Son Güncelleme:') }} {{ $fileTime }}</p>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>(:) İki nokta ile başlayan kelimeler çeviri yapılmadan yazılmalıdır. Orjinal metinde ":(kelime)"
                yazan metin varsa çeviri metninde (:) iki noktalı kelime orjinal olarak yazılmalıdır!</strong>
            <br>
            Örneğin; Orjinal Metin: "Home :page" > Çeviri Metin: "Anasayfa :page"
        </div>

        <div class="table-responsive">
            <table class="table table-responsive table-striped table-bordered w-100">
                <thead>
                <tr>
                    <th scope="col" class="bg-secondary text-white"></th>
                    <th scope="col" class="w-30 bg-secondary text-white">{{ __('Orjinal Metin') }}</th>
                    <th scope="col" class="w-60 bg-secondary text-white">{{ __('Çeviri Metni') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th scope="col"></th>
                    <th scope="col" class="w-30">
                        <div class="input-group flex-nowrap">
                            <input type="text" class="form-control" wire:model.live="searchOriginal"
                                   placeholder="{{ __('Ara') }}.."/>
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                        </div>
                    </th>
                    <th scope="col" class="w-60">
                        <div class="input-group flex-nowrap">
                            <input type="text" class="form-control" wire:model.live="searchTranslate"
                                   placeholder="{{ __('Ara') }}.."/>
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                        </div>
                    </th>
                </tr>
                @if($languageTranslations = $this->translations())
                    @foreach($languageTranslations as $languageTranslation)
                        <tr>
                            <th scope="row">{{ $languageTranslation->id }}</th>
                            <td>{{ $languageTranslation->key }}</td>
                            <td>
                        <textarea rows="{{ strlen($languageTranslation->key) >= 104 ? 3 : 1}}"
                                  class="form-control"
                                  id="translations.{{ $languageTranslation->id }}"
                                  wire:model.blur="translations.{{ $languageTranslation->id }}.value"
                                  placeholder="{{ $languageTranslation->key }}.."
                        ></textarea>
                            </td>
                        </tr>
                    @endforeach
                @else
                @endif
                </tbody>
            </table>
        </div>

        <div>
            {{ $languageTranslations->links() }}
        </div>

    </form>
</div>
