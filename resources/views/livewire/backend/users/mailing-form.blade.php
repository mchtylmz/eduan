<div>
    @if($errors->any())
        <div class="mb-3">
            @foreach ($errors->all() as $error)
                <span class="bg-danger text-white px-5 py-2 me-3">{{ $error }}</span>
            @endforeach
        </div>
    @endif

    <form wire:submit.prevent="send" wire:confirm="{{ __('E-posta gönderilecektir, işleme devam edilsin mi?') }}" novalidate>
        <div class="row">
            <div class="col-lg-4 mb-3">
                <label class="form-label" for="locale">{{ __( 'Dil') }}</label>
                <select id="locale" class="form-control" wire:model="locale" required>
                    <option value="" hidden>{{ __('Seçiniz') }}</option>
                    @foreach($languages = data()->languages(active: true) as $locale)
                        <option value="{{ $locale->code }}">{{ str($locale->code)->upper() }} - {{ $locale->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-12 mb-3">
                <label class="form-label" for="email">{{ __('E-posta Adresi') }}</label>
                <textarea rows="1"
                          class="form-control"
                          id="email"
                          wire:model="email"
                          placeholder="{{ __('E-posta Adresi') }}.."
                ></textarea>
            </div>
            <div class="col-lg-12 mb-3">
                <label class="form-label" for="subject">{{ __('Mail Konu Başlığı') }}</label>
                <textarea rows="1"
                          class="form-control"
                          id="subject"
                          wire:model="subject"
                          placeholder="{{ __('Mail Konu Başlığı') }}.."
                ></textarea>
            </div>

            <div class="col-lg-12 mb-3" wire:ignore>
                <label class="form-label" for="content">{{ __('Mail İçeriği') }}</label>
                <p class="my-1 border px-3">
                    {{ __('Kullanıcı Parametreleri') }}:
                    <span class="py-1 px-2 mx-1">[ad]</span>
                    <span class="py-1 px-2 mx-1">[soyad]</span>
                    <span class="py-1 px-2 mx-1">[ad_soyad]</span>
                    <span class="py-1 px-2 mx-1">[email]</span>
                    <span class="py-1 px-2 mx-1">[telefon]</span>
                </p>
                <x-tinymce.editor name="content" height="360" value="" />
            </div>

        </div>

        <div class="mb-3 text-center py-2">
            <button type="submit" class="btn btn-alt-success px-4" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-paper-plane mx-2 fa-faw"></i> {{ __('E-posta Gönder') }}
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                </div>
            </button>
        </div>
    </form>

</div>
