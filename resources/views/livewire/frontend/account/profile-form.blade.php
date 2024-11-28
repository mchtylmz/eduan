<div>
    <form wire:submit="save" class="account-form row justify-content-between">
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

        <div class="account-form-button mt-3 d-flex justify-content-center">
            <button type="submit" class="account-btn w-50" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-save-times me-1"></i>
                    <span class="fw-medium fs-6">{{ __('Bilgilerimi Güncelle') }}</span>
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
