@extends('frontend.layouts.app')
@section('content')
<section class="member-page notifications-page" data-web-push-page>
    <div class="container">
        <header class="notifications-page-heading">
            <div><h1>{{ $title }}</h1><p>{{ __('notifications.page_intro') }}</p></div>
            <a class="notifications-back" href="{{ route('frontend.profile') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ __('study.back_account') }}</a>
        </header>
        @if(session('notificationMessage'))<p class="notifications-feedback" role="status">{{ session('notificationMessage') }}</p>@endif

        <section class="notifications-preferences" aria-labelledby="notifications-device-title" data-push-layout data-state="pending">
            <div class="notifications-status-row">
                <div class="notifications-status-topline">
                    <span class="notifications-symbol" aria-hidden="true"><i class="fa-solid fa-bell"></i></span>
                    <span class="notifications-state" data-push-badge data-state="{{ $ready ? 'pending' : 'unavailable' }}">{{ __($ready ? 'notifications.ready_state' : 'notifications.unavailable_state') }}</span>
                </div>
                <div class="notifications-status-copy">
                    <h2 id="notifications-device-title" data-push-title>{{ __('notifications.start_title') }}</h2>
                    <p data-push-status role="status" aria-live="polite">{{ __($ready ? 'notifications.status_intro' : 'notifications.not_configured') }}</p>
                </div>
            </div>
            <ul class="notifications-benefits" data-push-benefits>
                <li><i class="fa-solid fa-bullseye" aria-hidden="true"></i><span>{{ __('notifications.benefit_study') }}</span></li>
                <li><i class="fa-regular fa-message" aria-hidden="true"></i><span>{{ __('notifications.benefit_personal') }}</span></li>
                <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>{{ __('notifications.benefit_optional') }}</span></li>
            </ul>
            <details class="notifications-settings" data-push-setup>
                <summary><i class="fa-solid fa-bell" aria-hidden="true"></i><span data-push-setup-title>{{ __('notifications.enable') }}</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></summary>
                <div class="notifications-settings-body">
                    <div data-push-onboarding>
                        <ol class="notifications-enable-steps">
                            <li><span class="notifications-step-number" aria-hidden="true">1</span><div><h3>{{ __('notifications.step_consent') }}</h3>
                                <label class="notifications-consent"><input type="checkbox" data-push-consent @disabled(! $ready)><span>{{ __('notifications.consent') }}</span></label>
                            </div></li>
                            <li><span class="notifications-step-number" aria-hidden="true">2</span><div><h3>{{ __('notifications.step_permission') }}</h3>
                                <button type="button" class="notifications-primary" data-push-enable @disabled(! $ready)><i class="fa-solid fa-bell" aria-hidden="true"></i><span data-push-enable-label>{{ __('notifications.enable') }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                                <p class="notifications-step-hint">{{ __('notifications.permission_step_help') }}</p>
                            </div></li>
                        </ol>
                        <p class="notifications-privacy"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>{{ __('notifications.privacy_short') }}</p>
                        <div class="notifications-guide" data-ios-guide hidden>
                            <h3>{{ __('notifications.ios_title') }}</h3>
                            <ol><li>{{ __('notifications.ios_share') }}</li><li>{{ __('notifications.ios_add') }}</li><li>{{ __('notifications.ios_open') }}</li><li>{{ __('notifications.ios_allow') }}</li></ol>
                        </div>
                        <details class="notifications-about" data-browser-guide><summary>{{ __('notifications.permission_help') }}</summary><p class="notifications-guide">{{ __('notifications.browser_help') }}</p></details>
                        @unless($ready)<p class="notifications-unavailable"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>{{ __('notifications.not_configured') }}</span></p>@endunless
                    </div>
                    <p data-push-active-help hidden>{{ __('notifications.active_help') }}</p>
                    <details class="notifications-about">
                        <summary>{{ __('notifications.about') }}</summary>
                        <div class="notifications-explanation">
                            @if($explanation && trim($explanation->content))
                                {!! \App\Helpers\NotificationExplanation::forDisplay($explanation->content) !!}
                            @else <p>{{ __('notifications.explanation_fallback') }}</p> @endif
                        </div>
                    </details>
                    <button type="button" class="notifications-back notifications-check" data-push-check @disabled(! $ready)><i class="fa-solid fa-rotate" aria-hidden="true"></i>{{ __('notifications.check') }}</button>
                </div>
            </details>
            <noscript><p class="notifications-settings-intro">{{ __('notifications.javascript_help') }}</p></noscript>
        </section>

        <nav class="notifications-section-links" aria-label="{{ __('notifications.page_sections') }}">
            <a class="notifications-back" href="#notifications-history-title"><i class="fa-regular fa-envelope" aria-hidden="true"></i>{{ __('notifications.recent') }}</a>
            <a class="notifications-back" href="#notifications-devices-title"><i class="fa-solid fa-laptop" aria-hidden="true"></i>{{ __('notifications.devices') }}</a>
        </nav>
        <div class="notifications-details">
            <section class="notifications-panel" aria-labelledby="notifications-history-title">
                <header class="notifications-panel-heading"><div><h2 id="notifications-history-title"><i class="fa-regular fa-envelope" aria-hidden="true"></i>{{ __('notifications.recent') }}</h2><p>{{ __('notifications.history_help') }}</p></div></header>
                <div class="notifications-panel-body">
                    @forelse($messages as $message)
                        <article class="notifications-message"><span class="notifications-message-mark" aria-hidden="true"></span><div><h3>{{ $message->title }}</h3><p>{{ $message->body }}</p><time datetime="{{ $message->completed_at->toIso8601String() }}">{{ dateFormat($message->completed_at, 'j F Y, H:i') }}</time></div></article>
                    @empty
                        <div class="notifications-empty"><i class="fa-regular fa-envelope" aria-hidden="true"></i><h3>{{ __('notifications.no_messages') }}</h3><p>{{ __('notifications.history_empty_help') }}</p></div>
                    @endforelse
                </div>
            </section>
            <section class="notifications-panel" aria-labelledby="notifications-devices-title" data-push-device-panel>
                <header class="notifications-panel-heading"><div><h2 id="notifications-devices-title"><i class="fa-solid fa-laptop" aria-hidden="true"></i>{{ __('notifications.devices') }}</h2><p>{{ __('notifications.devices_help') }}</p></div><span class="notifications-count">{{ $devices->whereNull('revoked_at')->count() }}</span></header>
                <div class="notifications-panel-body" data-push-devices>
                    <p class="notifications-device-feedback" data-push-devices-status role="status"></p>
                    @forelse($devices as $device)
                        <article class="notifications-device" data-push-device="{{ $device->subscription_id }}" data-push-revoked="{{ $device->revoked_at ? '1' : '0' }}">
                            <span class="notifications-device-symbol" aria-hidden="true"><i class="fa-solid {{ str_contains($device->label, 'iPhone') ? 'fa-mobile-screen-button' : 'fa-desktop' }}"></i></span>
                            <div class="notifications-device-copy"><h3>{{ $device->label }}</h3><span class="notifications-current-device" data-push-current hidden>{{ __('notifications.current_device') }}</span><p>{{ __('notifications.last_connected') }} {{ dateFormat($device->verified_at, 'j F Y, H:i') }}</p>@if($device->revoked_at)<p class="notifications-device-pending">{{ __('notifications.revoke_pending') }}</p>@endif</div>
                            <form method="POST" action="{{ route('frontend.notifications.revoke', $device->id) }}" data-push-revoke data-subscription="{{ $device->subscription_id }}">@csrf<button class="notifications-remove" type="submit"><i class="fa-solid fa-bell-slash" aria-hidden="true"></i> {{ __($device->revoked_at ? 'notifications.retry_revoke' : 'notifications.disable_device') }}</button></form>
                        </article>
                    @empty
                        <div class="notifications-empty"><i class="fa-solid fa-desktop" aria-hidden="true"></i><h3>{{ __('notifications.no_devices') }}</h3><p>{{ __('notifications.devices_empty_help') }}</p></div>
                    @endforelse
                </div>
            </section>

        </div>
    </div>
</section>
@endsection
