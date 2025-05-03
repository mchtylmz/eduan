<ul>
    <li><a href="{{ route('frontend.home') }}">{{ __('Anasayfa') }}</a></li>

    <li class="menu-has-child d-unset d-sm-none">
        <a href="javascript:void(0);">{{ __('Dersler') }}</a>
        <ul class="submenu">
            @if($lessons = data()->lessons(hits: false, limit: 9))
                @foreach($lessons as $lesson)
                    <li>
                        <a href="{{ route('frontend.lesson', $lesson->code) }}">{{ $lesson->name }}</a>
                    </li>
                @endforeach
            @endif
            <li>
                <a  href="{{ route('frontend.lessons') }}">{{ __('Tüm Dersler') }}</a>
            </li>
        </ul>
    </li>

    <li><a href="{{ route('frontend.tests') }}">{{ __('Testler') }}</a></li>

    <li><a href="{{ route('frontend.exams') }}">{{ __('Sınavlar') }}</a></li>

    <li><a href="{{ route('frontend.blog') }}">{{ __('Blog') }}</a></li>

    <li><a href="{{ route('frontend.contact') }}">{{ __('İletişim') }}</a></li>

    <li class="menu-has-child d-unset d-sm-none">
        <a href="{{ route('login') }}">
            <i class="fa fa-sign-in me-1"></i>
            {{ __('Oturum Aç') }}
        </a>
        <ul class="submenu">
            <li><a href="{{ route('login') }}">{{ __('Giriş Yap') }}</a></li>
            <li><a href="{{ route('frontend.register') }}">{{ __('Kayıt Ol') }}</a></li>
        </ul>
    </li>
</ul>
