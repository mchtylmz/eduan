@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAccount])
@endpush
@section('content')

    <!-- lessons area start -->
    <section class="h10_category-area pt-40 pb-50 bg-white">
        <div class="container">
            @includeIf('frontend.account.tab')

            <div class="course_details-wrap my-4">
                <div class="course_details-tab-button">
                    <ul class="nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation" style="width: 20%">
                            <a class="nav-link {{ request('tab') !== 'exams' ? 'active': '' }} px-4"
                                    href="{{ route('frontend.stats', ['tab' => 'tests']) }}">
                                <i class="fa fa-fw fa-poll d-none d-sm-block"></i>
                                <span>{{ __('Sınavlar') }}</span>
                            </a>
                        </li>
                        <li class="nav-item" role="presentation" style="width: 20%">
                            <a class="nav-link {{ request('tab') == 'exams' ? 'active': '' }} px-4"
                                    href="{{ route('frontend.stats', ['tab' => 'exams']) }}">
                                <i class="fa fa-fw fa-pen-clip d-none d-sm-block"></i>
                                <span>{{ __('Testler') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="course_details-tab-content">
                    <div class="tab-content h4_faq-area" id="pills-tests">
                        @livewire('frontend.account.stats-table', ['tab' => request('tab', 'tests')])
                    </div>
                </div>

            </div>
        </div>
    </section>
    <!-- lessons area end -->

@endsection
