<div>
    <form wire:submit.prevent="submit" novalidate>
        @csrf
        <div class="footer-subscribe-form mb-1">
            <input type="email" placeholder="{{ __('E-posta Adresiniz') }}" wire:model="email">
            <button type="submit" wire:loading.attr="disabled">
                <div wire:loading.remove>{{ __('Abone Ol') }}</div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse" style="animation-duration: 0.6s"></i>
                </div>
            </button>
        </div>

        @error('email')<small class="text-danger fw-bold mb-3">{{ $message }}</small>@enderror

        <div class="footer-subscribe-condition">
            <label class="condition_label">
                @if($page = \App\Models\Page::find(settings()->privacyPage ?? 0))
                    <a class="text-decoration-underline" target="_blank" href="{{ route('frontend.page', $page->slug) }}">{{ __('Site kullanım koşullarını ve gizlilik kurallarını okudum, kabul ediyorum.') }}</a>
                @else
                    {{ __('Site kullanım koşullarını ve gizlilik kurallarını okudum, kabul ediyorum.') }}
                @endif
                <input type="checkbox" wire:model="acceptTerms" @checked($acceptTerms)>
                <span class="check_mark"></span>
            </label>
        </div>
    </form>
</div>
