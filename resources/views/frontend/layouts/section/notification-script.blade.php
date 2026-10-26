@auth
    <script type="application/json" id="eduan-web-push-config">{!! json_encode([
        'ready' => \App\Helpers\WebPushSettings::ready(),
        'devicesUrl' => route('frontend.notifications'),
        'contextUrl' => route('frontend.notifications.context'), 'registerUrl' => route('frontend.notifications.register'),
        'accountKey' => \App\Helpers\WebPushSettings::externalId(auth()->user()),
        'messages' => collect(['consent_required','prepare','request','allowed','denied','unsupported','service_error','loading','ios_browser','enable','enabled_state','disabled_state','ready_state','loading_state','unavailable_state','devices_refresh_error','start_title','active_title','device_title','manage_settings'])->mapWithKeys(fn($key)=>[$key=>__('notifications.'.$key)])->all(),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script defer src="{{ asset('assets/web-push.js') }}?v={{ config('app.version') }}"></script>
@endauth
