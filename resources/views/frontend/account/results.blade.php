@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAccount])
@endpush
@section('content')

    <!-- lessons area start -->
    <section class="h10_category-area pt-40 pb-50 bg-white">
        <div class="container">
            @includeIf('frontend.account.tab')
            <div class="my-3">
                @livewire('frontend.account.solved-exams')
            </div>
        </div>
    </section>
    <!-- lessons area end -->

@endsection
