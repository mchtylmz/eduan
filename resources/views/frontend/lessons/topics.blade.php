@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverTopics
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', [
        'coverImage' => settings()->coverTopics,
        'previousPage' => ['title' => __('Dersler'), 'url' => route('frontend.lessons')]
    ])
@endpush
@section('content')

    <!-- topics area start -->
    <section class="h10_category-area pt-30 pb-50 bg-white">
        <div class="container">
            @livewire('frontend.lessons.list-topics', ['lesson' => $lesson])
        </div>
    </section>
    <!-- topics area end -->
@endsection
