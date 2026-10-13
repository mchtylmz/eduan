@php
    $metadata = $seo ?? \App\Helpers\FrontendSeo::metadata($title ?? null, $page ?? null);
    $metaDescription = \App\Helpers\FrontendSeo::text($description ?? null) ?: $metadata['description'];
    $metaImage = !empty($image) ? (str_starts_with($image, 'http') ? $image : url(getImage($image))) : $metadata['image'];
@endphp
<meta name="description" content="{{ $metaDescription }}">
@if($metadata['keywords'])
    <meta name="keywords" content="{{ $metadata['keywords'] }}">
@endif
<meta property="og:type" content="{{ $metadata['type'] }}">
<meta property="og:site_name" content="{{ $metadata['brand'] }}">
<meta property="og:title" content="{{ $metadata['title'] }}">
<meta property="og:description" content="{{ $metaDescription }}">
@if($metadata['locale'] === 'nl')
    <meta property="og:locale" content="nl_BE">
@endif
@if($metadata['canonical'])
    <meta property="og:url" content="{{ $metadata['canonical'] }}">
    <meta name="twitter:url" content="{{ $metadata['canonical'] }}">
@endif
<meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $metadata['title'] }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if($metaImage)
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:alt" content="{{ $metadata['heading'] }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <meta name="twitter:image:alt" content="{{ $metadata['heading'] }}">
@endif
@if($metadata['record'] instanceof \App\Models\Blog)
    <meta property="article:published_time" content="{{ $metadata['record']->published_at?->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $metadata['record']->updated_at?->toIso8601String() }}">
@endif
