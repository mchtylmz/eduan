<div>
    <form wire:submit="save" class="account-form row justify-content-between">
        @csrf
        <div class="account-form-item col-lg-6 mb-15">
            <div class="account-form-label">
                <label for="profile-name">{{ __('account.name') }}</label>
            </div>
            <div class="account-form-input">
                <input type="text" placeholder="{{ __('account.name') }}" id="profile-name" autocomplete="given-name" wire:model="name" required>
            </div>
            @error('name')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
        </div>
        <div class="account-form-item col-lg-6 mb-15">
            <div class="account-form-label">
                <label for="profile-surname">{{ __('account.surname') }}</label>
            </div>
            <div class="account-form-input">
                <input type="text" placeholder="{{ __('account.surname') }}" id="profile-surname" autocomplete="family-name" wire:model="surname" required>
            </div>
            @error('surname')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
        </div>
        <div class="account-form-item col-lg-12 mb-15">
            <div class="account-form-label">
                <label for="profile-email">{{ __('account.email') }}</label>
            </div>
            <div class="account-form-input">
                <input type="email" placeholder="{{ __('account.email') }}" id="profile-email" autocomplete="email" wire:model="email" required>
            </div>
            @error('email')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
        </div>
        <div class="account-form-item col-lg-12 mb-15">
            <div class="account-form-label">
                <label for="profile-phone">{{ __('Telefon Numarası') }}</label>
            </div>
            <div class="account-form-input">
                <input type="tel" placeholder="{{ __('Telefon Numarası') }}" id="profile-phone" autocomplete="tel" wire:model="phone">
            </div>
            @error('phone')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
        </div>

        <div class="account-form-button mt-3 d-flex justify-content-center">
            <button type="submit" class="account-btn" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-save me-1"></i>
                    <span class="fw-medium fs-6">{{ __('account.save') }}</span>
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
