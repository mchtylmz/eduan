@extends('frontend.layouts.app')
@section('content')
<section class="member-page pt-30 pb-50"><div class="container">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-4"><h1 class="member-page-title">{{ $title }}</h1><a class="btn btn-outline-secondary" href="{{ route('frontend.profile') }}">{{ __('account.profile') }}</a></div>
    @if(session('notificationMessage'))<div class="alert alert-info" role="status">{{ session('notificationMessage') }}</div>@endif
    <section class="member-form-card mb-4"><div class="member-form-body">
        @if($explanation && trim($explanation->content)) {!! $explanation->content !!} @else <p>{{ __('notifications.explanation_fallback') }}</p> @endif
        <div data-web-push-page>
            <div class="alert alert-info" data-ios-guide hidden>
                <h2 class="h5">{{ __('notifications.ios_title') }}</h2>
                <ol><li>{{ __('notifications.ios_share') }}</li><li>{{ __('notifications.ios_add') }}</li><li>{{ __('notifications.ios_open') }}</li><li>{{ __('notifications.ios_allow') }}</li></ol>
            </div>
            <div class="alert alert-info" data-browser-guide><h2 class="h5">{{ __('notifications.browser_title') }}</h2><p>{{ __('notifications.browser_help') }}</p></div>
            @unless($ready)<div class="alert alert-warning">{{ __('notifications.not_configured') }}</div>@endunless
            <label class="d-flex align-items-start gap-2 mb-3"><input type="checkbox" data-push-consent class="mt-1"><span>{{ __('notifications.consent') }}</span></label>
            <button type="button" class="btn btn-primary" data-push-enable @disabled(! $ready)>{{ __('notifications.enable') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-push-check @disabled(! $ready)>{{ __('notifications.check') }}</button>
            <p data-push-status role="status" class="mt-3" aria-live="polite"></p>
        </div>
    </div></section>
    <section class="member-form-card mb-4"><header class="member-form-heading"><div><h2>{{ __('notifications.devices') }}</h2><p>{{ __('notifications.devices_help') }}</p></div></header><div class="member-form-body">
        @forelse($devices as $device)
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom py-3"><div><strong>{{ $device->label }}</strong><div class="small text-muted">{{ dateFormat($device->verified_at) }}</div>@if($device->revoked_at)<p class="small text-muted mb-0">{{ __('notifications.revoke_pending') }}</p>@endif</div>
                <form method="POST" action="{{ route('frontend.notifications.revoke', $device->id) }}" data-push-revoke data-subscription="{{ $device->subscription_id }}">@csrf<button class="btn btn-outline-danger" type="submit">{{ __($device->revoked_at ? 'notifications.retry_revoke' : 'notifications.disable_device') }}</button></form>
            </div>
        @empty<p class="text-muted">{{ __('notifications.no_devices') }}</p>@endforelse
    </div></section>
    <section class="member-form-card"><header class="member-form-heading"><div><h2>{{ __('notifications.recent') }}</h2></div></header><div class="member-form-body">
        @forelse($messages as $message)<article class="border-bottom py-3"><h3 class="h6">{{ $message->title }}</h3><p class="text-break" style="white-space:pre-wrap">{{ $message->body }}</p><span class="small text-muted">{{ dateFormat($message->completed_at) }}</span></article>@empty<p class="text-muted">{{ __('notifications.no_messages') }}</p>@endforelse
    </div></section>
</div></section>
@endsection
