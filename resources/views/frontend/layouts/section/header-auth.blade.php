@auth()
@php
    $headerUser = request()->user();
    $headerLinks = [
        ['frontend.profile', __('account.profile'), 'fa-user'],
    ];
    if ($headerUser->can('study:access')) {
        $headerLinks[] = ['frontend.study', __('study.my_study'), 'fa-calendar-check'];
    }
    $headerLinks = array_merge($headerLinks, [
        ['frontend.favorite', __('account.favorites'), 'fa-bookmark'],
        ['frontend.solved', __('account.tests'), 'fa-pen-alt'],
        ['frontend.solved.exams', __('account.exams'), 'fa-book-alt'],
    ]);
    if (settings()->leagueStatus === 'active') {
        $headerLinks[] = ['frontend.my.leagues', __('account.leagues'), 'fa-trophy'];
    }
    $headerLinks[] = ['frontend.stats', __('account.stats'), 'fa-chart-line'];
    if ($headerUser->can('dashboard:access')) {
        $headerLinks[] = ['admin.home.index', __('account.admin'), 'fa-cogs'];
    }
@endphp
<div class="dropdown account-dropdown">
    <button class="header-account-toggle" type="button" id="header-account-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-controls="header-account-menu" aria-label="{{ __('account.title') }}">
        <i class="fa fa-user" aria-hidden="true"></i>
        <span>{{ __('account.title') }}</span>
        <i class="fa fa-angle-down" aria-hidden="true"></i>
    </button>
    <ul id="header-account-menu" class="dropdown-menu dropdown-menu-end header-account-menu" aria-labelledby="header-account-toggle">
        <li class="header-account-identity">
            <span class="header-account-avatar" aria-hidden="true">{{ mb_substr($headerUser->name, 0, 1).mb_substr($headerUser->surname, 0, 1) }}</span>
            <strong>{{ $headerUser->display_name }}</strong>
        </li>
        @foreach($headerLinks as [$route, $label, $icon])
            <li>
                <a @class(['dropdown-item', 'is-current' => request()->routeIs($route)]) href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>
                    <i class="fa {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
                </a>
            </li>
        @endforeach
        <li class="header-account-exit">
            <a class="dropdown-item" href="{{ route('frontend.logout') }}"><i class="fa fa-sign-out" aria-hidden="true"></i><span>{{ __('account.logout') }}</span></a>
        </li>
    </ul>
</div>
@endauth
