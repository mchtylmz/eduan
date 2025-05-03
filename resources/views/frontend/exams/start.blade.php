@extends('frontend.layouts.app')
@section('content')
    <!-- course details area start -->
    @auth()
        @if(auth()->user()->can('tests:solve'))
            <div class="row">
                <div class="col-sm-12 order-2 order-sm-1">
                    @livewire('frontend.exams.solve-form', ['test' => $test])
                </div>
            </div>
        @else
            <section class="course_details-area bg-white pt-25 pb-50">
                <div class="container exam_details">
                    <div class="text-danger alert alert-danger p-3 d-flex align-items-center">
                        <i class="fa-light fa-unlock-alt fa-2x fw-fw me-3"></i>
                        <p class="text-danger fw-semibold mb-0">
                            {!! settingLocale('examSolveNotPremiumDescription') !!}
                        </p>
                    </div>
                </div>
            </section>
        @endcan
    @else
        <section class="course_details-area bg-white pt-25 pb-50">
            <div class="container exam_details">
                <div class="text-danger alert alert-danger p-3 d-flex align-items-center">
                    <i class="fa fa-exclamation-square fa-2x fw-fw me-3"></i>
                    <p class="text-danger fw-semibold mb-0">
                        {!! settingLocale('examSolveNotAuthDescription') !!}
                    </p>
                </div>
            </div>
        </section>
    @endauth
    <!-- course details area end -->
@endsection
