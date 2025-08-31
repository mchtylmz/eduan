<div>
    @if(count($tests = $this->tests()))
        <div class="row">
            @foreach($tests as $test)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="h2_course-item mb-20 border">
                        <div class="h2_course-content pb-4">
                            <div class="h2_course-content-top">
                                <div class="h2_course-rating">
                                    <a href="{{ route('frontend.test.detail', $test->code) }}">
                                        <i class="fa-light fa-language me-1"></i> {{ $test->language->name ?? '-' }}
                                    </a>
                                </div>
                            </div>
                            <h5 class="h2_course-content-title mb-3">
                                <a href="{{ route('frontend.test.detail', $test->code) }}">{{ $test->name }}</a>
                            </h5>
                            <div class="h2_course-content-info mb-0">
                                <span><i class="fa-thin fa-eye"></i>{{ $test->visibility->name() ?? '' }}</span>
                            </div>
                        </div>
                        <div class="h2_course-content-bottom">
                            @if(!$showNotComplete)
                            @php
                                $resultStats = examResultStats(exam: $test, user: user());
                                $results_count = $resultStats['results_count'] ?? 0;
                                $correct_count = $resultStats['correct_count'] ?? 0;
                                $incorrect_count = $resultStats['incorrect_count'] ?? 0;
                            @endphp
                            <span>
                                <i class="fa-light fa-question-circle me-1"></i>
                                {{ $results_count }} {{ __('Sonuç') }}
                            </span>
                            <span>
                                    <i class="fa-light fa-poll me-1"></i>
                                {{ $correct_count }} {{ __('Doğru') }}
                                /
                                {{ $incorrect_count }} {{ __('Yanlış') }}
                            </span>
                            @else
                                <span>
                                    <i class="fa-light fa-question-circle me-1"></i>
                                    {{ $test->questions_count }} {{ __('Soru') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($showPaginate)
                <div class="col-12">
                    {{ $tests->links('components.frontend.livewire-pagination') }}
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fa fa-exclamation-circle mx-2"></i>
            <strong class="mx-2">{{ __('Gösterilecek sonuç bulunamadı!') }}</strong>
        </div>
    @endif

</div>

