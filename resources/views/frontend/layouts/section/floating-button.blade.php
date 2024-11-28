@if($link = settings()->socialWhatsapp)
    <a target="_blank" href="https://wa.me/{{ $link }}?text={{ urlencode(settingLocale('socialWhatsappText')) }}" class="float-whatsapp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>
@endif
