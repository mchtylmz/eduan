<div class="row mb-1">
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center gap-2 px-2 py-3 border rounded-2 {{ routeIs('frontend.profile') ? 'active' : '' }}"
           href="{{ route('frontend.profile') }}">
            <i class="fa-light fa-user fa-2x mx-1"></i>
            <p class="mb-0 text-black fw-medium fs-5">{{ __('Hesap Bilgilerim') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 {{ routeIs('frontend.favorite') ? 'active' : '' }}"
           href="{{ route('frontend.favorite') }}">
            <i class="fa-solid fa-bookmark fa-2x mx-1"></i>
            <p class="mb-0 text-black fw-medium fs-5"> {{ __('Favori Testlerim') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 {{ routeIs('frontend.solved') ? 'active' : '' }}"
           href="{{ route('frontend.solved') }}">
            <i class="fa-light fa-pen-clip fa-2x mx-1"></i>
            <p class="mb-0 text-black fw-medium fs-5">{{ __('Çözdüğüm Testler') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 {{ routeIs('frontend.solved.notTests') ? 'active' : '' }}"
           href="{{ route('frontend.solved.notTests') }}">
            <i class="fa-light fa-pen-alt-slash fa-2x mx-1"></i>
            <p class="mb-0 text-black fw-medium fs-5">{{ __('Çözmediğim Testler') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 {{ routeIs('frontend.solved.exams') ? 'active' : '' }}"
           href="{{ route('frontend.solved.exams') }}">
            <i class="fa-light fa-book-alt fa-2x mx-1"></i>
            <p class="mb-0 text-black fw-medium fs-5">{{ __('Çözdüğüm Sınavlar') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 {{ routeIs('frontend.stats') ? 'active' : '' }}"
           href="{{ route('frontend.stats') }}">
            <i class="fa-light fa-chart-line fa-2x mx-1"></i>
            <p class="mb-0 text-black fw-medium fs-5">{{ __('İstatistikler') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 bg-secondary"
           href="{{ route('admin.home.index') }}">
            <i class="fa-light fa-cogs fa-2x mx-1 text-white"></i>
            <p class="mb-0 text-black fw-medium fs-5 text-white">{{ __('Yönetim Paneli') }}</p>
        </a>
    </div>
    <div class="col-6 col-lg-3 my-1 px-1 account-menu">
        <a class="d-grid d-sm-flex align-items-center justify-content-center text-center  gap-2 px-2 py-3 border rounded-2 bg-danger"
           href="{{ route('frontend.logout') }}">
            <i class="fa-light fa-sign-out fa-2x mx-1 text-white"></i>
            <p class="mb-0 text-black fw-medium fs-5 text-white">{{ __('Çıkış Yap') }}</p>
        </a>
    </div>
</div>
