<div>
    <form wire:submit.prevent="update" novalidate class="account-form">
        @csrf

        <div class="account-form-item mb-15">
            <div class="account-form-label">
                <label for="password">{{ __('Yeni Parolanız') }}</label>
            </div>
            <div class="account-form-input account-form-input-pass">
                <input type="{{ $passwordInputType }}"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       wire:model="password"
                       placeholder="******"
                       autocomplete="off" required>
                <span style="right: 5px;">
                    <button class="btn btn-light" type="button" wire:click="changeType">
                            @if($passwordInputType == 'text')
                            <i class="fa-light fa-eye-slash mx-2"></i>
                        @else
                            <i class="fa-light fa-eye mx-2"></i>
                        @endif
                    </button>
                </span>
                @error('password')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
        </div>
        <div class="account-form-item mb-15">
            <div class="account-form-label">
                <label for="password_confirmation">{{ __('Tekrar Yeni Parolanız') }}</label>
            </div>
            <div class="account-form-input account-form-input-pass">
                <input type="{{ $passwordInputType }}"
                       id="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       wire:model="password_confirmation"
                       placeholder="******"
                       autocomplete="off" required>
                <span style="right: 5px;">
                    <button class="btn btn-light" type="button" wire:click="changeType">
                            @if($passwordInputType == 'text')
                            <i class="fa-light fa-eye-slash mx-2"></i>
                        @else
                            <i class="fa-light fa-eye mx-2"></i>
                        @endif
                    </button>
                </span>
                @error('password_confirmation')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>
        </div>
        <div class="account-form-button mt-3">
            <button type="submit" class="account-btn" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-unlock me-1"></i>
                    <span class="fw-medium fs-6">{{ __('Parola Güncelle') }}</span>
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
