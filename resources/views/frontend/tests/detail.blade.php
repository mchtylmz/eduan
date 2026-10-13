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
                    <a href="{{ route('frontend.tests') }}">{{ __('Testler') }}</a>
                    <span>{{ $title }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- course details area start -->
    <section class="course_details-area practice-detail bg-white pt-25 pb-50">
        <div class="container bg-white">
            <div class="row">
                <div class="col-xl-8 col-lg-8">
                    <div class="course_details-wrap mb-55">
                        <h1 class="h3 course_details-title mt-2 mb-4">{{ $exam->name }}</h1>
                        <div class="course_details-tab-button">
                            <ul class="nav nav-pills" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pills-results-tab" data-bs-toggle="pill" data-bs-target="#pills-results" type="button" role="tab" aria-controls="pills-results" aria-selected="true">
                                        <i class="fa fa-chart-line" aria-hidden="true"></i><span>{{ __('learning.my_results') }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="false">
                                        <i class="fa fa-info-square"></i><span>{{ __('learning.description') }}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-four-tab" data-bs-toggle="pill" data-bs-target="#pills-four" type="button" role="tab" aria-controls="pills-four" aria-selected="false">
                                        <i class="fa fa-comment"></i>
                                        <span>{{ __('learning.reviews') }}</span>
                                        <span class="badge bg-danger text-white fw-bold review-badge">
                                            {{ $exam->publicReviewsCount() }}
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="course_details-tab-content">
                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-results" role="tabpanel" aria-labelledby="pills-results-tab" tabindex="0">
                                    @livewire('frontend.tests.personal-results', ['exam' => $exam])
                                </div>
                                <div class="tab-pane fade" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">
                                    <div class="course_details-content" lang="{{ $exam->locale }}">
                                        @if(filled(trim(strip_tags(html_entity_decode($exam->content ?? '')))) || preg_match('/<(img|video|audio|iframe|svg)\b/i', $exam->content ?? ''))
                                            {!! html_entity_decode($exam->content) !!}
                                        @else
                                            <p class="member-empty">{{ __('learning.empty_description') }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-four" role="tabpanel" aria-labelledby="pills-four-tab" tabindex="0">
                                    @livewire('frontend.tests.review-form-and-list', ['exam' => $exam])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4">
                   @livewire('frontend.tests.detail-statistic-list', ['exam' => $exam])
                </div>
            </div>
        </div>
    </section>
    <!-- course details area end -->
@endsection
@push('script')
    <script>
        // review-badge
        $(document).ready(function() {
            Livewire.on('update-badge', function (value) {
                let badgeCount = value[0]['count'] !== undefined ? value[0]['count'] : false;
                if (badgeCount !== false) {
                    $('.review-badge').html(badgeCount);
                }
            });
        });

    </script>
@endpush
