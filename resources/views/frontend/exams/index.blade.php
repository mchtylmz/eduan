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
            @livewire('frontend.exams.list-exams', [
                'showHits' => false,
                'showPaginate' => true,
                'paginate' => 12,
                'showSearch' => true
            ])
        </div>
    </section>
    <!-- course area end -->
@endsection
