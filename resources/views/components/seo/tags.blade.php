@props([
    'title' => $title ?? settingLocale('siteTitle'),
    'description' => $description ?? settingLocale('siteDescription'),
    'keywords' => $keywords ?? settingLocale('siteKeywords'),
    'image' => getImage(settings()->siteLogo)
])
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $title }}, {{ $description }}">
<meta name="keywords" content="{{ $title }},{{ $keywords }}">
<meta name="news_keywords" content="{{ $title }},{{ $keywords }}">
<meta name="image" content="{{ str_starts_with($image, 'http') ? $image : getImage($image) }}">

<!-- Facebook Meta Tags -->
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $title }}, {{ $description }}">
<meta property="og:keywords" content="{{ $title }},{{ $keywords }}">
<meta property="og:image" content="{{ str_starts_with($image, 'http') ? $image : getImage($image) }}">

<!-- Twitter Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:domain" content="{{ request()->host() }}">
<meta property="twitter:url" content="{{ url('/') }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $title }}, {{ $description }}">
<meta name="twitter:keywords" content="{{ $title }},{{ $keywords }}">
<meta name="twitter:image" content="{{ str_starts_with($image, 'http') ? $image : getImage($image) }}">
<meta name="tweetmeme-title" content="{{ $title }}">
