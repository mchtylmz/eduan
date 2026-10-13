@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'description' => $page->brief,
        'keywords' => $page->keywords,
        'image' => settings()->siteLogo
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverPage, 'showHeading' => false])
@endpush
@section('content')
    <section class="contact-area pt-30">
        <div class="container">
            @if(empty(settings()->coverPage))
                <div class="contact-content pr-40 mb-40 pt-5 pt-sm-0">
                    <h1 class="h3 contact-title mb-25">{{ $title }}</h1>
                </div>
            @endif

            <div class="pb-3 mb-3">
                {!! $page->content !!}
            </div>
        </div>
    </section>
@endsection
