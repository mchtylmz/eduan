@extends('frontend.layouts.app')
@section('content')
<section class="member-page notifications-page">
    <div class="container">
        <header class="notifications-page-heading">
            <div><h1>{{ $title }}</h1><p>{{ __('notifications.page_intro') }}</p></div>
            <a class="notifications-back" href="{{ route('frontend.profile') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ __('account.profile') }}</a>
        </header>
        @if(session('notificationMessage'))<p class="notifications-feedback" role="status">{{ session('notificationMessage') }}</p>@endif

        <section class="notifications-setup" aria-labelledby="notifications-setup-title">
            <div class="notifications-introduction">
                <span class="notifications-symbol" aria-hidden="true"><i class="fa-solid fa-bell"></i></span>
                <h2 id="notifications-setup-title">{{ __('notifications.setup_title') }}</h2>
                <div class="notifications-explanation">
                    @if($explanation && trim($explanation->content))
                        {!! \App\Helpers\NotificationExplanation::forDisplay($explanation->content) !!}
                    @else <p>{{ __('notifications.explanation_fallback') }}</p> @endif
                </div>
                <ul class="notifications-benefits">
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i>{{ __('notifications.benefit_study') }}</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i>{{ __('notifications.benefit_personal') }}</li>
                    <li><i class="fa-solid fa-check" aria-hidden="true"></i>{{ __('notifications.benefit_optional') }}</li>
                </ul>
            </div>
            <div class="notifications-permission" data-web-push-page>
                <div class="notifications-permission-heading">
                    <h3>{{ __('notifications.this_browser') }}</h3>
                    <span class="notifications-state" data-push-badge data-state="{{ $ready ? 'pending' : 'unavailable' }}">{{ __($ready ? 'notifications.ready_state' : 'notifications.unavailable_state') }}</span>
                </div>
                <div class="notifications-guide" data-ios-guide hidden>
                    <h4>{{ __('notifications.ios_title') }}</h4>
                    <ol><li>{{ __('notifications.ios_share') }}</li><li>{{ __('notifications.ios_add') }}</li><li>{{ __('notifications.ios_open') }}</li><li>{{ __('notifications.ios_allow') }}</li></ol>
                </div>
                <div class="notifications-guide" data-browser-guide>
                    <p>{{ __('notifications.browser_help') }}</p>
                </div>
                @unless($ready)<p class="notifications-unavailable"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>{{ __('notifications.not_configured') }}</span></p>@endunless
                <label class="notifications-consent"><input type="checkbox" data-push-consent @disabled(! $ready)><span>{{ __('notifications.consent') }}</span></label>
                <div class="notifications-permission-actions">
                    <button type="button" class="notifications-primary" data-push-enable @disabled(! $ready)>{{ __('notifications.enable') }}</button>
                    <button type="button" class="notifications-text-button" data-push-check @disabled(! $ready)>{{ __('notifications.check') }}</button>
                </div>
                <p data-push-status role="status" class="notifications-live-status" aria-live="polite"></p>
            </div>
        </section>

        <div class="notifications-details">
            <section class="notifications-panel" aria-labelledby="notifications-devices-title">
                <header class="notifications-panel-heading"><div><h2 id="notifications-devices-title">{{ __('notifications.devices') }}</h2><p>{{ __('notifications.devices_help') }}</p></div><span class="notifications-count">{{ $devices->whereNull('revoked_at')->count() }}</span></header>
                <div class="notifications-panel-body">
                    @forelse($devices as $device)
                        <article class="notifications-device">
                            <span class="notifications-device-symbol" aria-hidden="true"><i class="fa-solid {{ str_contains($device->label, 'iPhone') ? 'fa-mobile-screen-button' : 'fa-desktop' }}"></i></span>
                            <div class="notifications-device-copy"><h3>{{ $device->label }}</h3><p>{{ dateFormat($device->verified_at) }}</p>@if($device->revoked_at)<p class="notifications-device-pending">{{ __('notifications.revoke_pending') }}</p>@endif</div>
                            <form method="POST" action="{{ route('frontend.notifications.revoke', $device->id) }}" data-push-revoke data-subscription="{{ $device->subscription_id }}">@csrf<button class="notifications-remove" type="submit">{{ __($device->revoked_at ? 'notifications.retry_revoke' : 'notifications.disable_device') }}</button></form>
                        </article>
                    @empty
                        <div class="notifications-empty"><i class="fa-solid fa-desktop" aria-hidden="true"></i><h3>{{ __('notifications.no_devices') }}</h3><p>{{ __('notifications.devices_empty_help') }}</p></div>
                    @endforelse
                </div>
            </section>
            <section class="notifications-panel" aria-labelledby="notifications-history-title">
                <header class="notifications-panel-heading"><div><h2 id="notifications-history-title">{{ __('notifications.recent') }}</h2><p>{{ __('notifications.history_help') }}</p></div></header>
                <div class="notifications-panel-body">
                    @forelse($messages as $message)
                        <article class="notifications-message"><span class="notifications-message-mark" aria-hidden="true"></span><div><h3>{{ $message->title }}</h3><p>{{ $message->body }}</p><time datetime="{{ $message->completed_at->toIso8601String() }}">{{ dateFormat($message->completed_at) }}</time></div></article>
                    @empty
                        <div class="notifications-empty"><i class="fa-regular fa-envelope" aria-hidden="true"></i><h3>{{ __('notifications.no_messages') }}</h3><p>{{ __('notifications.history_empty_help') }}</p></div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</section>
@endsection
