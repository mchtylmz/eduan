@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverTests
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverTests])
@endpush
@section('content')
    <!-- course area start -->
    <section class="innerPage_course-area pt-30 pb-50">
        <div class="container">
            @livewire('frontend.tests.list-tests', [
                'showHits' => false,
                'showPaginate' => true,
                'paginate' => 21,
                'showSearch' => true,
                'topic_id' => $topic->id ?? 0
            ])
        </div>
    </section>
    <!-- course area end -->
@endsection
