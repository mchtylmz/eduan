<div>
    @if(count($exams = $this->exams()))
        <div class="row g-3 member-course-grid">
            @foreach($exams as $exam)
                <div class="col-xxl-6 col-lg-6">
                    <div class="h2_course-item border">
                        <div class="h2_course-content">
                            <div class="h2_course-content-top">
                                <div class="h2_course-rating">
                                    <a href="{{ route('frontend.solved.exams.detail', $exam->code) }}">
                                        <i class="fa-light fa-language me-1"></i> {{ $exam->language->name ?? '-' }}
                                    </a>
                                </div>
                            </div>
                            <h5 class="h2_course-content-title mb-1">
                                <a href="{{ route('frontend.solved.exams.detail', $exam->code) }}">{{ $exam->name }}</a>
                            </h5>
                        </div>
                        <div class="h2_course-content-bottom">
                            <span>
                                <i class="fa-light fa-pen-clip me-1"></i>
                                {{ $exam->results_count }} {{ __('account.answers') }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($showPaginate)
                <div class="col-12">
                    {{ $exams->links('components.frontend.livewire-pagination') }}
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fa fa-exclamation-circle mx-2"></i>
            <strong class="mx-2">{{ __('account.empty') }}</strong>
        </div>
    @endif

</div>

