<div>
    @if($afterRegister)
        <div class="alert alert-success p-4 d-flex align-items-center my-3">
            <i class="fa fa-check-double fa-2x mx-2"></i>
            <strong>
                {{ __('Kayıt işlemi başarılı, e-posta adresinize gönderilen onay maili sonrasında giriş yapabilirsiniz!') }}
            </strong>
        </div>
    @else
        <form wire:submit.prevent="save" novalidate class="account-form row justify-content-between">
            @csrf

            <div class="account-form-item col-lg-6 mb-15">
                <div class="account-form-label">
                    <label>{{ __('İsim') }}</label>
                </div>
                <div class="account-form-input">
                    <input type="text" placeholder="{{ __('İsim') }}" wire:model="name" required>
                </div>
                @error('name')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="account-form-item col-lg-6 mb-15">
                <div class="account-form-label">
                    <label>{{ __('Soyisim') }}</label>
                </div>
                <div class="account-form-input">
                    <input type="text" placeholder="{{ __('Soyisim') }}" wire:model="surname" required>
                </div>
                @error('surname')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="account-form-item col-lg-12 mb-15">
                <div class="account-form-label">
                    <label>{{ __('E-posta Adresi') }}</label>
                </div>
                <div class="account-form-input">
                    <input type="email" placeholder="{{ __('E-posta Adresi') }}" wire:model="email" required>
                </div>
                @error('email')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="account-form-item col-lg-12 mb-15">
                <div class="account-form-label">
                    <label>{{ __('Telefon Numarası') }}</label>
                </div>
                <div class="account-form-input">
                    <input type="tel" placeholder="{{ __('Telefon Numarası') }}" wire:model="phone">
                </div>
                @error('phone')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="account-form-item col-lg-6 mb-15">
                <div class="account-form-label">
                    <label>{{ __('Parola') }}</label>
                </div>
                <div class="account-form-input account-form-input-pass">
                    <input type="{{ $passwordInputType }}" placeholder="*********" wire:model="password" required>
                    <span style="right: 5px;">
                    <button class="btn btn-light" type="button" wire:click="changeType">
                            @if($passwordInputType == 'text')
                            <i class="fa-light fa-eye-slash mx-2"></i>
                        @else
                            <i class="fa-light fa-eye mx-2"></i>
                        @endif
                    </button>
                </span>
                </div>
                @error('password')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="account-form-item col-lg-6 mb-15">
                <div class="account-form-label">
                    <label>{{ __('Tekrar Yeni Parola') }}</label>
                </div>
                <div class="account-form-input account-form-input-pass">
                    <input type="{{ $passwordInputType }}" placeholder="*********" wire:model="password_confirmation" required>
                    <span style="right: 5px;">
                    <button class="btn btn-light" type="button" wire:click="changeType">
                            @if($passwordInputType == 'text')
                            <i class="fa-light fa-eye-slash mx-2"></i>
                        @else
                            <i class="fa-light fa-eye mx-2"></i>
                        @endif
                    </button>
                </span>
                </div>
                @error('password_confirmation')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
            <div class="account-form-condition col-lg-12">
                <label class="condition_label">
                    {{ __('Site kullanım koşullarını ve gizlilik kurallarını okudum, kabul ediyorum.') }}
                    <input type="checkbox" wire:model="acceptTerms" @checked($acceptTerms)>
                    <span class="check_mark"></span>
                </label>
                @error('acceptTerms')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>

            <div class="account-form-button offset-md-3 col-lg-6">
                <button type="submit" class="account-btn" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-user-plus me-1"></i>
                        <span class="fw-medium fs-6">{{ __('Kayıt Ol') }}</span>
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>
        </form>
    @endif
</div>
