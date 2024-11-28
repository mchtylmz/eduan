@props([
    'mw' => $mw ?? false
])
@if($siteLogoWhite = settings()->siteLogoWhite)
    <a href="/">
        @includeIf('frontend.layouts.section.logo', ['mw' => $mw])
    </a>
@endif

