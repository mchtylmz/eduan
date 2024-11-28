<div>
    <form wire:submit.prevent="login" novalidate class="account-form">
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
        <div class="account-form-item mb-15">
            <div class="account-form-label">
                <label for="password">{{ __('Parolanız') }}</label>
                <a href="{{ route('frontend.recover.password') }}">{{ __('Parolamı Unuttum ?') }}</a>
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
        <div class="account-form-condition">
            <label class="condition_label">{{ __('Beni Hatırla') }}
                <input type="checkbox"
                       id="remember"
                       wire:model="remember"
                        @checked($remember)>
                <span class="check_mark"></span>
            </label>
        </div>
        <div class="account-form-button">
            <button type="submit" class="account-btn" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-sign-in-alt me-1"></i>
                    <span class="fw-medium fs-6">{{ __('Giriş Yap') }}</span>
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
