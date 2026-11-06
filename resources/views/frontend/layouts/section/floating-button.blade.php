@if(\App\Enums\StatusEnum::ACTIVE->value == settings()->floatWhatsapp && ($link = settings()->socialWhatsapp))
    <a target="_blank" href="https://wa.me/{{ $link }}?text={{ urlencode(settingLocale('socialWhatsappText')) }}" class="float-whatsapp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>
@endif

@auth
@if(\App\Helpers\WebPushSettings::enabled())
    <a href="{{ route('frontend.notifications') }}" @class(['float-notifications', 'is-alone' => !settings()->socialWhatsapp || settings()->floatWhatsapp !== \App\Enums\StatusEnum::ACTIVE->value]) aria-label="{{ __('notifications.title') }}" title="{{ __('notifications.title') }}">
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
    </a>
@endif
@endauth
