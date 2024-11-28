<div>
    @if($message)
        <div class="alert alert-success p-3 d-flex align-items-center">
            <i class="fa fa-fw fa-2x fa-check-double me-2"></i>
            <p class="fw-bold mb-0">{{ $message }}</p>
        </div>
    @else
        <form wire:submit.prevent="submit" novalidate class="account-form">
            @csrf
            <div class="account-form-item mb-20">
                <div class="account-form-label">
                    <label for="username">{{ __('E-posta Adresiniz') }}</label>
                </div>
                <div class="account-form-input">
                    <input type="text"
                           class="form-control @error('username') is-invalid @enderror"
                           placeholder="example@email.com"
                           id="username"
                           wire:model="username"
                           autocomplete="off" required>
                </div>
                @error('username')<small class="text-danger fw-bold">{{ $message }}</small>@enderror
            </div>

            <div class="account-form-button">
                <button type="submit" class="account-btn" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-link me-1"></i>
                        <span class="fw-medium fs-6">{{ __('Gönder') }}</span>
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>
        </form>
    @endif
</div>
