@php
    $accountUser = request()->user();
    $accountLinks = [
        ['route' => 'frontend.profile', 'key' => 'profile', 'icon' => 'fa-user'],
        ['route' => 'frontend.favorite', 'key' => 'favorites', 'icon' => 'fa-bookmark'],
        ['route' => 'frontend.solved', 'key' => 'tests', 'icon' => 'fa-pen-clip'],
        ['route' => 'frontend.solved.exams', 'key' => 'exams', 'icon' => 'fa-book-alt'],
    ];
    if ($accountUser->can('study:access')) {
        $accountLinks[] = ['route' => 'frontend.study', 'key' => 'study', 'label' => __('study.my_study'), 'icon' => 'fa-calendar-check'];
    }
    if (\App\Helpers\WebPushSettings::enabled() && \App\Models\WebPushSubscription::where('user_id', $accountUser->id)->whereNull('revoked_at')->exists()) {
        $accountLinks[] = ['route' => 'frontend.notifications', 'key' => 'notifications', 'label' => __('notifications.title'), 'icon' => 'fa-bell'];
    }
    if (settings()->leagueStatus === 'active') {
        $accountLinks[] = ['route' => 'frontend.my.leagues', 'key' => 'leagues', 'icon' => 'fa-trophy'];
    }
    $accountLinks[] = ['route' => 'frontend.stats', 'key' => 'stats', 'icon' => 'fa-chart-line'];
    $currentAccountKey = match (true) {
        request()->routeIs('frontend.favorite') => 'favorites',
        request()->routeIs('frontend.solved', 'frontend.solved.notTests') => 'tests',
        request()->routeIs('frontend.solved.exams', 'frontend.solved.exams.detail') => 'exams',
        request()->routeIs('frontend.my.leagues') => 'leagues',
        request()->routeIs('frontend.stats') => 'stats',
        default => 'profile',
    };
@endphp
<div class="member-hub" x-data="{ menuOpen: false }">
    <header class="member-heading">
        <span class="member-avatar" aria-hidden="true">{{ mb_substr($accountUser->name, 0, 1).mb_substr($accountUser->surname, 0, 1) }}</span>
        <h2>{{ $accountUser->display_name }}</h2>
    </header>
    <button type="button" class="member-menu-toggle" @click="menuOpen = !menuOpen"
            :aria-expanded="menuOpen.toString()" aria-expanded="false" aria-controls="member-account-menu">
        <span><i class="fa-light fa-bars" aria-hidden="true"></i>{{ __('account.'.$currentAccountKey) }}</span>
        <i class="fa-light fa-chevron-down" :class="{ 'is-open': menuOpen }" aria-hidden="true"></i>
    </button>
    <nav id="member-account-menu" class="member-navigation" aria-label="{{ __('account.navigation') }}"
         x-show="menuOpen" x-cloak>
        @foreach($accountLinks as $item)
            @php($selected = $currentAccountKey === $item['key'])
            <a href="{{ route($item['route']) }}" @click="menuOpen = false" @class(['member-nav-link', 'is-active' => $selected]) @if($selected) aria-current="page" @endif>
                <span class="member-nav-icon"><i class="fa-light {{ $item['icon'] }}" aria-hidden="true"></i></span>
                <span class="member-nav-copy">{{ $item['label'] ?? __('account.'.$item['key']) }}</span>
            </a>
        @endforeach
        @if(auth()->user()->can('dashboard:access'))
            <a href="{{ route('admin.home.index') }}" class="member-nav-link member-admin-link" @click="menuOpen = false">
                <span class="member-nav-icon"><i class="fa-light fa-cogs" aria-hidden="true"></i></span>
                <span class="member-nav-copy">{{ __('account.admin') }}</span>
            </a>
        @endif
        <a href="{{ route('frontend.logout') }}" class="member-nav-link member-logout" @click="menuOpen = false">
            <span class="member-nav-icon"><i class="fa-light fa-sign-out" aria-hidden="true"></i></span>
            <span class="member-nav-copy">{{ __('account.logout') }}</span>
        </a>
    </nav>
</div>
