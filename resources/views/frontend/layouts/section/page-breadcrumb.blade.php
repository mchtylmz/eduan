@props([
    'coverImage' => $coverImage ?? false,
    'previousPage' => $previousPage ?? []
])
<!-- breadcrumb area start -->
@if($coverImage)
<section class="breadcrumb-area bg-default" data-background="{{ getImage($coverImage) }}">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-content">
                    @if(!empty($title))
                        <h2 class="breadcrumb-title">{{ $title }}</h2>
                    @endif
                    <div class="breadcrumb-list">
                        <a href="/">{{ __('Anasayfa') }}</a>
                        @if(!empty($previousPage['title']) && !empty($previousPage['url']))
                            <a href="{{ $previousPage['url'] }}">{{ $previousPage['title'] }}</a>
                        @endif
                        @if(!empty($title))
                            <span>{{ $title }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@else
<div class="pt-4 pb-3" style="background-color: #f6f7f99c !important">
    <div class="container">
        <div class="breadcrumb-content">
            <div class="breadcrumb-list justify-content-start">
                <a href="/">{{ __('Anasayfa') }}</a>
                @if(!empty($previousPage['title']) && !empty($previousPage['url']))
                    <a href="{{ $previousPage['url'] }}">{{ $previousPage['title'] }}</a>
                @endif
                @if(!empty($title))
                    <span>{{ $title }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
<!-- breadcrumb area end -->
