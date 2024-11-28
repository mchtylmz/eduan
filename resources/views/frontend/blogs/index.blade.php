@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverBlog
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverBlog])
@endpush
@section('content')

    <!-- blog area start -->
    <section class="innerPage_blog-area pt-30 pb-50">
        <div class="container">
            @livewire('frontend.blogs.list-blogs')
        </div>
    </section>
    <!-- blog area end -->
@endsection
