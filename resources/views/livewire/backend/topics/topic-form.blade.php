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
            <div class="col-lg-12 mb-3" wire:ignore>
                <label class="form-label" for="lesson_id">{{ __('Ders') }}</label>
                <select id="lesson_id" class="form-control selectpicker" data-live-search="true" data-size="6" wire:model="lesson_id">
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach($this->lessons() as $lesson)
                        <option value="{{ $lesson->id }}" @selected($lesson->id == $lesson_id)>{{ $lesson->display_name }}</option>
                    @endforeach
                </select>
                <x-badge.error field="lesson_id"/>
            </div>

            <div class="col-lg-3 mb-3">
                <label class="form-label" for="code">{{ __('Kodu') }}</label>
                <input type="text" class="form-control" id="code" wire:model="code"
                       placeholder="{{ __('Kodu') }}.."/>
                <x-badge.error field="code"/>
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

            @foreach(data()->languages(active: true) as $index => $language)
                <div class="col-lg-12 mb-2">
                    <x-badge.language :language="$language" />
                </div>
                <div class="col-lg-5 mb-3">
                    <label class="form-label" for="title.{{ $language->code }}">
                        {{ __('Konu Başlığı') }}
                    </label>
                    <textarea rows="1"
                              class="form-control"
                              id="title.{{ $language->code }}"
                              wire:model="title.{{ $language->code }}"
                              placeholder="{{ __('Konu Başlığı') }}.."
                    ></textarea>
                </div>
                <div class="col-lg-7 mb-3">
                    <label class="form-label" for="description.{{ $language->code }}">
                        {{ __('Açıklama') }}
                    </label>
                    <textarea rows="2"
                              class="form-control"
                              id="description.{{ $language->code }}"
                              wire:model="description.{{ $language->code }}"
                              placeholder="{{ __('Açıklama') }}.."
                    ></textarea>
                </div>
            @endforeach

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
