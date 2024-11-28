@guest()
    @if(settings()->registerStatus == \App\Enums\StatusEnum::ACTIVE->value)
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle header-btn theme-btn theme-btn-medium px-3" type="button" id="auth-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-user-plus me-0 me-sm-2"></i>
                <span class="d-none d-sm-block">{{ __('Oturum Aç') }}</span>
            </button>
            <ul class="dropdown-menu py-0" aria-labelledby="auth-dropdown">
                <li>
                    <a class="dropdown-item border-bottom py-2" href="{{ route('login') }}">
                        <i class="fa fa-sign-in-alt mx-2"></i>
                        <span>{{ __('Giriş Yap') }}</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('frontend.register') }}">
                        <i class="fa fa-user-plus mx-2"></i>
                        <span>{{ __('Kayıt Ol') }}</span>
                    </a>
                </li>
            </ul>
        </div>
    @else
        <div class="dropdown">
            <a class="btn btn-secondary header-btn theme-btn theme-btn-medium px-3" type="button" href="{{ route('login') }}">
                <i class="fa fa-user-plus me-0 me-sm-2"></i>
                <span class="d-none d-sm-block">{{ __('Oturum Aç') }}</span>
            </a>
        </div>
    @endif
@endguest
