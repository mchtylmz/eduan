<div>

    <form wire:submit.prevent="submit" novalidate class="contact-form">
        @csrf
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-6 mb-20">
                <div class="contact-form-input">
                    <input type="text" placeholder="{{ __('İsim Soyisim') }}" wire:model="name">
                    <span class="inner-icon"><i class="fa-thin fa-user"></i></span>
                </div>
                @error('name')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-6 mb-20">
                <div class="contact-form-input">
                    <input type="email" placeholder="{{ __('E-posta Adresi') }}" wire:model="email">
                    <span class="inner-icon"><i class="fa-thin fa-envelope"></i></span>
                </div>
                @error('email')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-6 mb-20">
                <div class="contact-form-input">
                    <input type="text" placeholder="{{ __('Telefon numarası') }}" wire:model="phone">
                    <span class="inner-icon"><i class="fa-thin fa-phone-volume"></i></span>
                </div>
                @error('phone')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-6 mb-20">
                <div class="contact-form-input">
                    <input type="text" placeholder="{{ __('Okul Adı') }}" wire:model="schoolName">
                    <span class="inner-icon"><i class="fa-thin fa-school"></i></span>
                </div>
                @error('schoolName')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="col-12 mb-20">
                <div class="contact-form-input contact-form-textarea">
                    <textarea wire:model="message" cols="30" rows="15" placeholder="{{ __('Mesajınız') }}..."></textarea>
                    <span class="inner-icon"><i class="fa-thin fa-pen"></i></span>
                </div>
                @error('message')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="col-12">
                <div class="contact-form-submit mb-20">
                    <div class="contact-form-btn">
                        <button type="submit" class="theme-btn contact-btn" wire:loading.attr="disabled">
                            <div wire:loading.remove>
                                <i class="fa fa-paper-plane mx-2 fa-faw"></i> {{ __('Mesajı Gönder') }}
                            </div>
                            <div wire:loading>
                                <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                            </div>
                        </button>
                    </div>
                    <div class="contact-form-condition">
                        <label class="condition_label">
                            @if($page = \App\Models\Page::find(settings()->privacyPage ?? 0))
                                <a class="text-decoration-underline" target="_blank" href="{{ route('frontend.page', $page->slug) }}">{{ __('Site kullanım koşullarını ve gizlilik kurallarını okudum, kabul ediyorum.') }}</a>
                            @else
                                {{ __('Site kullanım koşullarını ve gizlilik kurallarını okudum, kabul ediyorum.') }}
                            @endif
                            <input type="checkbox" wire:model="acceptTerms" @checked($acceptTerms)>
                            <span class="check_mark"></span>
                        </label>
                        @error('acceptTerms')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
