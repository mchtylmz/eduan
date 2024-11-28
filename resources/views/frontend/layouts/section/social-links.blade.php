@props([
    'active' => $active ?? false
])
<ul>
    @if($link = settings()->socialFacebook)
        <li>
            <a @class(['active' => $active]) target="_blank" href="https://facebook.com/{{ $link }}">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
        </li>
    @endif
    @if($link = settings()->socialX)
        <li>
            <a @class(['active' => $active]) target="_blank" href="https://twitter.com/{{ $link }}">
                <i class="fa-brands fa-x"></i>
            </a>
        </li>
    @endif
    @if($link = settings()->socialInstagram)
        <li>
            <a @class(['active' => $active]) target="_blank" href="https://instagram.com/{{ $link }}">
                <i class="fa-brands fa-instagram"></i>
            </a>
        </li>
    @endif
    @if($link = settings()->socialYoutube)
        <li>
            <a @class(['active' => $active]) target="_blank" href="https://youtube.com/{{ $link }}">
                <i class="fa-brands fa-youtube"></i>
            </a>
        </li>
    @endif
    @if($link = settings()->socialWhatsapp)
        <li>
            <a @class(['active' => $active]) target="_blank"
               href="https://wa.me/{{ $link }}?text={{ urlencode(settingLocale('socialWhatsappText')) }}">
                <i class="fa-brands fa-whatsapp"></i>
            </a>
        </li>
    @endif
</ul>
