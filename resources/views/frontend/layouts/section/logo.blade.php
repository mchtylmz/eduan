@props([
    'mw' => $mw ?? false
])
@if($siteLogo = settings()->siteLogo)
    <img src="{{ asset($siteLogo) }}" alt="{{ settingLocale('siteTitle') }}" @style(['max-width: 300px' => $mw])>
@endif
