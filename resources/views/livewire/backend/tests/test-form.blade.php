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
                    <label class="form-label" for="name">{{ __('Sınav Adı') }}</label>
                    <textarea
                        rows="1"
                        class="form-control"
                        id="name"
                        wire:model="name"
                        placeholder="{{ __('Sınav Adı') }}.."
                    ></textarea>
                    <x-badge.error field="name"/>
                </div>

                <div class="col-lg-12 mb-3" wire:ignore>
                    <label class="form-label" for="content">{{ __('Açıklama') }}</label>
                    <x-tinymce.editor name="content" value="{{ $content }}" height="240" />
                </div>

                <div class="col-lg-3 mb-3">
                    <label class="form-label" for="duration">{{ __('Süre') }}</label>
                    <div class="input-group" wire:ignore>
                        <input type="number" min="30" class="form-control" id="duration" wire:model.change="duration">
                        <span class="input-group-text">{{ __('saniye') }}</span>
                    </div>
                    <p class="m-0 bg-light py-1 px-3 rounded-0">
                       {{ $duration ?: 0 }} {{ __('saniye') }}  = {{ secondToMinutes($duration) }} {{ __('dakika') }}
                    </p>
                    <x-badge.error field="duration" />
                </div>

                <div class="col-lg-3 mb-3">
                    <label class="form-label" for="code">{{ __('Kodu') }}</label>
                    <input type="text" class="form-control" id="code" wire:model="code"
                           placeholder="{{ __('Kodu') }}.."/>
                    <x-badge.error field="code"/>
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

            @can($permission)
                <div class="mb-3 mt-0 text-center py-2">
                    <button type="submit" class="btn btn-alt-primary px-4" wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Sınavı Kaydet') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                        </div>
                    </button>
                </div>
            @endcan
        </form>
</div>
