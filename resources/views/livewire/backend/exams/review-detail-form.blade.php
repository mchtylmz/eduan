<div>
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="bg-body-light p-2 px-3">
                <label class="form-label" for="visibility">{{ __('Görünüm') }}</label>
                <div class="input-group mb-3">
                    <select id="visibility" class="form-control" wire:model="visibility">
                        <option value="" hidden>{{ __('Seçiniz') }}</option>
                        @foreach(\App\Enums\ReviewVisibilityEnum::options() as $optionKey => $optionName)
                            <option value="{{ $optionKey }}">{{ $optionName }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-secondary" type="button" wire:click="save">{{ __('Kaydet') }}</button>
                </div>
            </div>
        </div>
        <div class="col-lg-12 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('Test Adı') }}: <h5 class="mb-0">{{ $review->exam->name }}</h5>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('İsim Soyisim') }}: <h5 class="mb-0">{{ $review->user->display_name }}</h5>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('Değerlendirme Tarihi') }}: <h5 class="mb-0">{{ dateFormat($review->created_at, 'd/m/Y, H:i')}}</h5>
            </div>
        </div>
        <div class="col-lg-12 mb-3">
            <div class="bg-body-light p-2 px-3">
                {{ __('Değerlendirme') }}: <h5 class="mb-0">{{ $review->comment ?? '' }}</h5>
            </div>
        </div>
    </div>

    <hr>

    <form wire:submit.prevent="reply" novalidate>
        <div class="row">
            <div class="col-lg-12 mb-3">
                <label class="form-label" for="comment">{{ __('Yanıt Metni') }}</label>
                <textarea rows="4"
                          class="form-control"
                          id="comment"
                          wire:model="comment"
                          placeholder="{{ __('Yanıt Metni') }}.."
                ></textarea>
                <x-badge.error field="comment"/>
                <small>{{ __('Yanıt mesajınız yeni yorum olarak eklenir, istenirse sonradan yanıt silinebilir.') }}</small>
            </div>
        </div>

        <div class="mb-3 text-center py-2">
            <button type="submit" class="btn btn-alt-success px-4" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-paper-plane mx-2 fa-faw"></i> {{ __('Yanıtla') }}
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                </div>
            </button>
        </div>
    </form>

</div>
