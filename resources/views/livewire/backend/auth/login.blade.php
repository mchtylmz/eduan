<div>
    <!-- Sign In Form -->
    <form wire:submit.prevent="login">
        <div class="py-3">
            <div class="mb-3">
                <input type="text"
                       class="form-control form-control-lg form-control-alt @error('username') is-invalid @enderror"
                       id="username"
                       wire:model="username"
                       placeholder="{{ __('Kullanıcı adı') }}..."
                       autocomplete="off" required>
                @error('username')
                <div class="invalid-feedback animated fadeIn my-0">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <input type="{{ $passwordInputType }}"
                           class="form-control form-control-alt form-control-lg @error('password') is-invalid @enderror"
                           id="password"
                           wire:model="password"
                           placeholder="{{ __('Parola') }} ***"
                           autocomplete="off" required>
                    <button class="btn btn-secondary" type="button" wire:click="changeType">
                        @if($passwordInputType == 'text')
                            <i class="fa fa-eye-slash mx-1"></i>
                        @else
                            <i class="fa fa-eye mx-1"></i>
                        @endif
                    </button>
                </div>
                @error('password')
                <div class="invalid-feedback animated fadeIn my-0">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <div class="d-md-flex align-items-md-center justify-content-md-between">
                    <div class="form-check form-switch form-check-inline">
                        <input class="form-check-input"
                               type="checkbox"
                               id="remember"
                               wire:model="remember"
                               @checked($remember)>
                        <label class="form-check-label" for="remember">{{ __('Beni Hatırla') }}</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xxl-5">
                <button type="submit" class="btn w-100 btn-alt-primary px-4" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-fw fa-sign-in-alt me-1 opacity-50"></i> {{ __('Giriş Yap') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>
        </div>
    </form>
    <!-- END Sign In Form -->
</div>
