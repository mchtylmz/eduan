<div>
    @if($errors->any())
        <div class="mb-3">
            @foreach ($errors->all() as $error)
                <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
            @endforeach
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="title text-start">
                <h5 class="mb-3">{{ $answerAI->question->title }}</h5>
            </div>
            <div class="attachment text-left">
                @if($attachment = $answerAI->question->attachment)
                    <img class="w-100"
                         style="max-width: 600px; object-fit: contain"
                         src="{{ getImage($attachment) }}"
                         alt="{{ __('Soru') }}"/>
                @endif
            </div>

            <div class="solution mt-3">
                <div class="attachment text-left">
                    <img class="w-100"
                         style="max-width: 600px; object-fit: contain"
                         src="{{ getImage($answerAI->question->solution) }}?v={{ time() }}"
                         alt="{{ __('Soru Çözümü') }}"/>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <form wire:submit.prevent="save" novalidate>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label" for="locale">{{ __('Dil') }}</label>
                        <select id="locale" class="form-control" wire:model="locale">
                            <option value="" hidden>{{ __('Seçiniz') }}</option>
                            @foreach(data()->languages(active: true) as $language)
                                <option
                                    value="{{ $language->code }}" @selected($language->code == $answerAI->locale)>{{ str($language->code)->upper() }}
                                    - {{ $language->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label" for="report">{{ __('Raporlama') }}</label>
                        <select id="report" class="form-control" wire:model="report">
                            <option value="" hidden>{{ __('Seçiniz') }}</option>
                            <option value="{{ \App\Enums\YesNoEnum::YES }}">{{ __('Raporlandı, Hatalı') }}</option>
                            <option value="{{ \App\Enums\YesNoEnum::NO }}">{{ __('Raporlanmadı, Doğru') }}</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3" wire:ignore>
                    <label class="form-label" for="editorcontent">{{ __('Yanıt İçeriği') }}</label>
                    <x-tinymce.editor name="content" value="{{ $content }}" height="800"/>
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

    </div>

</div>
