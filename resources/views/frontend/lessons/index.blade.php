@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverLessons
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverLessons])
@endpush
@section('content')

    <!-- lessons area start -->
    <section class="h10_category-area pt-30 pb-50 bg-white">
        <div class="container">
           @livewire('frontend.lessons.list-lessons')
        </div>
    </section>
    <!-- lessons area end -->
@endsection
