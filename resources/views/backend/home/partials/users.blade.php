<section class="dashboard-panel">
    <header class="dashboard-panel-header">
        <span class="dashboard-icon"><i class="fa {{ $lastLogins ? 'fa-right-to-bracket' : 'fa-user-plus' }}" aria-hidden="true"></i></span>
        <div><h2>{{ $title }}</h2><p>{{ __('dashboard.last_six') }}</p></div>
    </header>
    <ul class="dashboard-user-list">
        @forelse($rows as $row)
            <li>
                <span class="dashboard-avatar" aria-hidden="true">{{ mb_substr($row['name'], 0, 1) }}{{ mb_substr($row['surname'], 0, 1) }}</span>
                <div class="dashboard-user-info"><strong>{{ $row['name'] }} {{ $row['surname'] }}</strong><span>{{ $row['username'] }}</span></div>
                <span class="dashboard-user-detail">
                    @if($lastLogins)
                        {{ dateFormat($row['last_login_at'], 'd M Y, H:i') }}
                    @else
                        <time datetime="{{ $row['created_at'] }}">{{ dateFormat($row['created_at'], 'd M Y, H:i') }}</time>
                        <span class="{{ $row['email_verified'] ? 'dashboard-correct' : 'text-muted' }}">{{ $row['email_verified'] ? __('Onaylı') : __('Onaylı Değil') }}</span>
                    @endif
                </span>
            </li>
        @empty
            <li class="dashboard-empty"><span>{{ __('dashboard.empty') }}</span></li>
        @endforelse
    </ul>
</section>
