@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverTests
    ])
@endpush
@section('content')
    <div class="pt-4 pb-3" style="background-color: #f6f7f9!important">
        <div class="container">
            <div class="breadcrumb-content">
                <div class="breadcrumb-list justify-content-start">
                    <a href="/">{{ __('Anasayfa') }}</a>
                    <a href="{{ route('frontend.exams') }}">{{ __('Sınavlar') }}</a>
                    <span>{{ $title }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- course details area start -->
    <section class="course_details-area bg-white pt-25 pb-50">
        <div class="container bg-white">
            <div class="row">
                <div class="col-xl-8 col-lg-8">
                    <div class="course_details-wrap mb-55">
                        <h3 class="course_details-title mt-2 mb-2">{{ $test->name }}</h3>
                        <div class="course_details-content">
                            {!! html_entity_decode($test->content) !!}
                        </div>

                        <div class="course_details-sections my-3">
                            @foreach($test->sections()->parentIsZero()->get() as $section)
                                <div class="block bg-body-light mb-2 px-3 py-2 rounded-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-dark">{{ $loop->iteration }}</span>
                                    <p class="my-0 text-dark fw-bold">{{ $section->name }}</p>
                                </div>

                                <div class="block bg-light mb-2 px-3 py-2 rounded-2">
                                    <ul class="list-group list-group-flush bg-transparent">
                                        @foreach($section->parents as $parent)
                                            <li class="list-group-item bg-transparent d-flex align-items-center gap-2">
                                                <span class="badge bg-secondary opacity-75">
                                                    {{ $loop->iteration }}
                                                </span>
                                                <span class="my-0 text-dark">
                                                    @if(App\Enums\TestSectionTypeEnum::QUESTION->is($parent->type))
                                                        {{ __('Soru') }}
                                                    @else
                                                        {{ $parent->name }}
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4">
                    @livewire('frontend.exams.detail-statistic-list', ['test' => $test])
                </div>
            </div>
        </div>
    </section>
    <!-- course details area end -->
@endsection
@push('script')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.exam-start-btn', function (e) {
                $(this).attr('disabled', 'disabled');
                $(this).append('<i class="fa fa-spinner fa-pulse mx-1"></i>');
            });
        });
    </script>
@endpush
