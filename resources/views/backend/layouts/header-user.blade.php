<a target="_blank" class="btn btn-sm btn-alt-success d-flex align-items-center mx-2" href="{{ url('/') }}">
    <i class="fa fa-fw fa-external-link me-1"></i>
    <span class="ms-1 d-none d-sm-block">{{ __('Siteyi Görüntüle') }}</span>
</a>
<div class="dropdown d-inline-block ms-2">
    <button type="button" class="btn btn-sm btn-alt-secondary d-flex align-items-center" id="page-header-locale-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="ms-1 d-block d-sm-none">{{ str(app()->getLocale())->upper() }}</span>
        <span class="ms-1 d-none d-sm-block">{{ data()->language(app()->getLocale())->name }}</span>
        <i class="fa fa-fw fa-angle-down opacity-50 ms-1 mt-1"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0 border-0" aria-labelledby="page-header-locale-dropdown">
        <div class="p-2">
            @foreach(data()->languages(active: true) as $locale)
                <a class="dropdown-item d-flex align-items-center justify-content-between" href="?lang={{ $locale->code }}">
                    <span class="fs-sm fw-medium">{{ str($locale->code)->upper() }} - {{ $locale->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
<div class="dropdown d-inline-block ms-2">
    <button type="button" class="btn btn-sm btn-alt-secondary d-flex align-items-center" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-fw fa-user"></i>
        <span class="ms-2">{{ user()->name }}</span>
        <i class="fa fa-fw fa-angle-down opacity-50 ms-1 mt-1"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0 border-0" aria-labelledby="page-header-user-dropdown">
        <div class="p-3 text-center bg-body-light border-bottom rounded-top">
            <p class="mt-2 mb-0 fw-medium">{{ user()->name }}</p>
            <p class="mb-0 text-muted fs-sm fw-medium">{{ user()->email }}</p>
        </div>
        <div class="p-2">
            <a target="_blank" class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('frontend.profile') }}">
                <span class="fs-sm fw-medium">{{ __('Hesap Bilgilerim') }}</span>
            </a>
            <div role="separator" class="dropdown-divider m-0"></div>
            <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('admin.logout') }}">
                <span class="fs-sm fw-medium">{{ __('Çıkış Yap') }}</span>
            </a>
        </div>
    </div>
</div>
