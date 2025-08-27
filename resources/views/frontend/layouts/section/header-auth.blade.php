@auth()
<div class="dropdown">
    <button class="btn btn-dark dropdown-toggle header-btn theme-btn theme-btn-medium px-3" type="button" id="auth-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-user me-0 me-sm-2"></i>
        <span class="d-none d-sm-block">{{ __('Hesabım') }}</span>
    </button>
    <ul class="dropdown-menu py-0" aria-labelledby="auth-dropdown">
        <li>
            <a class="dropdown-item border-bottom py-2" href="{{ route('frontend.profile') }}">
                <i class="fa fa-user mx-1"></i>
                <span>{{ __('Hesap Bilgilerim') }}</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item border-bottom py-2" href="{{ route('frontend.favorite') }}">
                <i class="fa fa-bookmark mx-1"></i>
                <span>{{ __('Favori Testlerim') }}</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item border-bottom py-2" href="{{ route('frontend.solved') }}">
                <i class="fa fa-pen-alt mx-1"></i>
                <span>{{ __('Çözdüğüm Testler') }}</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item border-bottom py-2" href="{{ route('frontend.solved') }}">
                <i class="fa fa-pen-alt-slash mx-1"></i>
                <span>{{ __('Yarım Kalan Testler') }}</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item border-bottom py-2" href="{{ route('frontend.solved.exams') }}">
                <i class="fa fa-book-alt mx-1"></i>
                <span>{{ __('Çözdüğüm Sınavlar') }}</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item border-bottom py-2" href="{{ route('frontend.solved.exams') }}">
                <i class="fa fa-chart-line mx-1"></i>
                <span>{{ __('İstatistikler') }}</span>
            </a>
        </li>
        @if(user()->can('dashboard:access'))
            <li>
                <a class="dropdown-item border-bottom py-2" href="{{ route('admin.home.index') }}">
                    <i class="fa fa-cogs mx-1"></i>
                    <span>{{ __('Yönetim Paneli') }}</span>
                </a>
            </li>
        @endif
        <li>
            <a class="dropdown-item border-bottom py-2 bg-danger text-white" href="{{ route('frontend.logout') }}">
                <i class="fa fa-sign-out mx-1"></i>
                <span>{{ __('Çıkış Yap') }}</span>
            </a>
        </li>
    </ul>
</div>
@endauth
