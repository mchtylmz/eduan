@if(\App\Helpers\PanelGuide::visibleTo(auth()->user()) && ($guideUrl = \App\Helpers\PanelGuide::url(settings()->panelGuideUrl)))
    <ul class="nav-main mt-4 pt-3">
        <li class="nav-main-item">
            <a class="nav-main-link text-white-50" href="{{ $guideUrl }}" target="_blank" rel="noopener noreferrer"
               title="{{ __('guide.new_tab') }}" data-panel-guide>
                <i class="nav-main-link-icon fa fa-book-open text-white-50" aria-hidden="true"></i>
                <span class="nav-main-link-name">{{ __('guide.title') }}</span>
            </a>
        </li>
    </ul>
@endif
