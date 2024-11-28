@if(settings()->multiLanguage == \App\Enums\StatusEnum::ACTIVE->value)
    <div class="h2_header-btn d-block">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle header-btn theme-btn theme-btn-locale theme-btn-medium px-3" type="button" id="auth-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-globe me-1"></i>
                <span>{{ app()->getLocale() }}</span>
            </button>
            <ul class="dropdown-menu py-0" aria-labelledby="auth-dropdown">
                @foreach(data()->languages(active: true) as $locale)
                    <li>
                        <a @class(['dropdown-item border-bottom py-2', 'active' => $locale->code == app()->getLocale()])
                           href="?lang={{ $locale->code }}">
                            <span class="text-uppercase">{{ $locale->code }}</span> -
                            <span>{{ $locale->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
