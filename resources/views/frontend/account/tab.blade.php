<div class="d-flex flex-wrap gap-0 gap-sm-2">

    <div class="col-sm col-6">
        <div class="h10_category-item mb-20 text-center h10_category_bg-1 py-3 {{ routeIs('frontend.profile') ? 'active' : '' }}">
            <div class="h10_category-item-icon mb-3">
                <a href="{{ route('frontend.profile') }}">
                    <i class="fa-light fa-user-alt fa-2x mx-1"></i>
                </a>
            </div>
            <h4 class="h10_category-item-title">
                <a href="{{ route('frontend.profile') }}">
                    {{ __('Hesap Bilgilerim') }}
                </a>
            </h4>
        </div>
    </div>

    <div class="col-sm col-6">
        <div class="h10_category-item mb-20 text-center h10_category_bg-2 py-3 px-3 {{ routeIs('frontend.favorite') ? 'active' : '' }}">
            <div class="h10_category-item-icon mb-3">
                <a href="{{ route('frontend.favorite') }}">
                    <i class="fa-solid fa-bookmark fa-2x mx-1"></i>
                </a>
            </div>
            <h4 class="h10_category-item-title">
                <a href="{{ route('frontend.favorite') }}">
                    {{ __('Favori Testlerim') }}
                </a>
            </h4>
        </div>
    </div>

    <div class="col-sm col-6">
        <div class="h10_category-item mb-20 text-center h10_category_bg-3 py-3 px-3 {{ routeIs('frontend.solved') ? 'active' : '' }}">
            <div class="h10_category-item-icon mb-3">
                <a href="{{ route('frontend.solved') }}">
                    <i class="fa-light fa-pen-alt fa-2x mx-1"></i>
                </a>
            </div>
            <h4 class="h10_category-item-title">
                <a href="{{ route('frontend.solved') }}">
                    {{ __('Çözdüğüm Testler') }}
                </a>
            </h4>
        </div>
    </div>

    <div class="col-sm col-6">
        <div class="h10_category-item mb-20 text-center h10_category_bg-6 py-3 px-3 {{ routeIs('frontend.solved.exams') ? 'active' : '' }}">
            <div class="h10_category-item-icon mb-3">
                <a href="{{ route('frontend.solved') }}">
                    <i class="fa-light fa-book-alt fa-2x mx-1"></i>
                </a>
            </div>
            <h4 class="h10_category-item-title">
                <a href="{{ route('frontend.solved.exams') }}">
                    {{ __('Çözdüğüm Sınavlar') }}
                </a>
            </h4>
        </div>
    </div>

    @if(auth()->user()?->can('dashboard:access'))
        <div class="col-sm col-6">
            <div class="h10_category-item mb-20 text-center bg-secondary text-white py-3">
                <div class="h10_category-item-icon mb-3">
                    <a href="{{ route('admin.home.index') }}">
                        <i class="fa-light fa-cogs fa-2x mx-1"></i>
                    </a>
                </div>
                <h4 class="h10_category-item-title text-white">
                    <a href="{{ route('admin.home.index') }}">
                        {{ __('Yönetim Paneli') }}
                    </a>
                </h4>
            </div>
        </div>
    @else
        <div class="col-sm col-6">
            <div class="h10_category-item mb-20 text-center bg-danger text-white py-3">
                <div class="h10_category-item-icon mb-3">
                    <a href="{{ route('frontend.logout') }}">
                        <i class="fa-light fa-sign-out fa-2x mx-1"></i>
                    </a>
                </div>
                <h4 class="h10_category-item-title text-white">
                    <a href="{{ route('frontend.logout') }}">
                        {{ __('Çıkış Yap') }}
                    </a>
                </h4>
            </div>
        </div>
    @endif

</div>
