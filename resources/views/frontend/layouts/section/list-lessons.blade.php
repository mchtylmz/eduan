<div class="h2_header-category d-none d-sm-block">
    <a href="javascript:void(0);">
        <i class="fa-solid fa-grid"></i>
        <span>{{ __('Dersler') }}</span>
    </a>
    <ul class="h2_header-category-submenu">
        @if($lessons = data()->lessons(hits: false, limit: 9))
            @foreach($lessons as $lesson)
                <li>
                    <a class="py-2" href="{{ route('frontend.lesson', $lesson->code) }}">{{ $lesson->name }}</a>
                </li>
            @endforeach
        @endif
        <li>
            <a class="py-2" href="{{ route('frontend.lessons') }}">{{ __('Tüm Dersler') }}</a>
        </li>
    </ul>
</div>
