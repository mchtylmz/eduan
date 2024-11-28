@extends('frontend.layouts.app')
@section('content')
    {{---
    <div class="pt-4 pb-3" style="background-color: #f6f7f9!important">
        <div class="container">
            <div class="breadcrumb-content">
                <div class="breadcrumb-list justify-content-start">
                    <a href="/">{{ __('Anasayfa') }}</a>
                    <a href="{{ route('frontend.tests') }}">{{ __('Testler') }}</a>
                    <a href="{{ route('frontend.test.detail', $exam->code) }}">{{ $exam->name }}</a>
                    <span>{{ $title }}</span>
                </div>
            </div>
        </div>
    </div>
    ---}}

    <!-- course details area start -->
    <section class="course_details-area bg-white pt-25 pb-50">
        <div class="container exam_details">
            @auth()
                @if(\App\Enums\VisibilityEnum::LOGGED->is($exam->visibility) || auth()->user()->can('exams:solve'))
                    <div class="row">
                        <div class="col-sm-12 order-2 order-sm-1">
                            @livewire('frontend.tests.solve-form', ['exam' => $exam])
                        </div>
                        {{---
                        <div class="col-xl-4 col-lg-4 order-1 order-sm-2">
                            @livewire('frontend.tests.solve-questions-statistic', ['exam' => $exam])
                        </div>
                        --}}
                    </div>
                @else
                    <div class="text-danger alert alert-danger p-3 d-flex align-items-center">
                        <i class="fa-light fa-unlock-alt fa-2x fw-fw me-3"></i>
                        <p class="text-danger fw-semibold mb-0">
                            {!! settingLocale('examSolveNotPremiumDescription') !!}
                        </p>
                    </div>
                @endcan
            @else
                <div class="text-danger alert alert-danger p-3 d-flex align-items-center">
                    <i class="fa fa-exclamation-square fa-2x fw-fw me-3"></i>
                    <p class="text-danger fw-semibold mb-0">
                        {!! settingLocale('examSolveNotAuthDescription') !!}
                    </p>
                </div>
            @endauth
        </div>
    </section>
    <!-- course details area end -->
@endsection
@push('script')
    <script>
        $(document).ready(function() {
            Livewire.on('scrollToSolution', function () {
                const solution = document.querySelector('.solution');
                const solutionPosition = solution.offsetTop !== undefined ? solution.offsetTop - 100 : 0;
                window.scrollTo({ top: solutionPosition, behavior: 'smooth' });
            });
            Livewire.on('scrollToQuestion', function () {
                const solveForm = document.querySelector('.solve-form');
                const solveFormPosition = solveForm.offsetTop !== undefined ? solveForm.offsetTop - 100 : 0;
                window.scrollTo({ top: solveFormPosition, behavior: 'smooth' });
            });

            const startTime = new Date();
            function updateElapsedTime() {
                const now = new Date();
                const elapsed = Math.floor((now - startTime) / 1000);

                const minutes = Math.floor(elapsed / 60);
                const seconds = (elapsed % 60) - 1;

                const elapsedTime = document.getElementById('elapsed-time');
                if (elapsedTime) {
                    elapsedTime.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }

            //let interval = setInterval(updateElapsedTime, 1000);

            Livewire.on('endTest', function () {
              //  clearInterval(interval);
            });
        });
    </script>
@endpush
