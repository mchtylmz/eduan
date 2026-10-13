<div>
    @if(count($tests = $this->tests()))
        <div class="row g-3 member-course-grid">
            @foreach($tests as $test)
                <div class="col-xxl-6 col-lg-6">
                    <div class="h2_course-item border">
                        <div class="h2_course-content">
                            <div class="h2_course-content-top">
                                <div class="h2_course-rating">
                                    <a href="{{ route('frontend.test.detail', $test->code) }}">
                                        <i class="fa-light fa-language me-1"></i> {{ $test->language->name ?? '-' }}
                                    </a>
                                </div>
                            </div>
                            <h5 class="h2_course-content-title mb-2">
                                <a href="{{ route('frontend.test.detail', $test->code) }}">{{ $test->name }}</a>
                            </h5>
                            <div class="h2_course-content-info mb-0">
                                <span><i class="fa-thin fa-eye"></i>{{ __($test->visibility === \App\Enums\VisibilityEnum::PREMIUM ? 'account.premium_only' : 'account.members_only') }}</span>
                            </div>
                        </div>
                        <div class="h2_course-content-bottom">
                            @if(!$showNotComplete)
                            <span>
                                <i class="fa-light fa-question-circle me-1"></i>
                                {{ $test->personal_results_count }} {{ __('account.results_count') }}
                            </span>
                            <span>
                                    <i class="fa-light fa-poll me-1"></i>
                                {{ $test->personal_correct_count }} {{ __('account.correct') }}
                                /
                                {{ $test->personal_incorrect_count }} {{ __('account.incorrect') }}
                            </span>
                            @else
                                <span>
                                    <i class="fa-light fa-question-circle me-1"></i>
                                    {{ $test->questions_count }} {{ __('account.questions') }}
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
            <strong class="mx-2">{{ __('account.empty') }}</strong>
        </div>
    @endif

</div>
