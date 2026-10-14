@auth
    <script type="application/json" id="eduan-web-push-config">{!! json_encode([
        'ready' => \App\Helpers\WebPushSettings::ready(),
        'contextUrl' => route('frontend.notifications.context'), 'registerUrl' => route('frontend.notifications.register'),
        'accountKey' => \App\Helpers\WebPushSettings::externalId(auth()->user()),
        'messages' => collect(['consent_required','prepare','request','allowed','denied','unsupported','service_error','loading','ios_browser','enable'])->mapWithKeys(fn($key)=>[$key=>__('notifications.'.$key)])->all(),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script defer src="{{ asset('assets/web-push.js') }}?v={{ config('app.version') }}"></script>
@endauth
