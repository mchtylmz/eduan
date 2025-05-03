<div>
    @if($showSearch)
        <div class="innerPage_course-top mb-30">
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-4 col-md-4 mb-0">
                    @if($search)
                        @if($word)
                            <div class="btn-group mb-3" role="group">
                                <button type="button" class="btn btn-secondary" disabled>{{ __('Ara') }}
                                    : {{ $word }}</button>
                                <button type="button" wire:click="clearSearch" class="btn btn-danger"><i
                                        class="fa fa-times"></i></button>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="col-xl-8 col-md-8">
                    <div class="innerPage_course-right mb-20">
                        <div class="innerPage_course-search">
                            <form wire:submit.prevent="searchExams" novalidate>
                                <input type="text" wire:model="word" placeholder="{{ __('Ara') }}..">
                                <button type="submit" class="innerPage_course-search-btn" wire:loading.attr="disabled">
                                    <i class="fa-thin fa-magnifying-glass"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(count($exams = $this->exams()))
        <div class="row">
            @foreach($exams as $exam)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="h2_course-item mb-20 border">
                        <div class="h2_course-content pb-4">
                            <div class="h2_course-content-top">
                                <div class="h2_course-rating">
                                    <a href="{{ route('frontend.exam.detail', $exam->code) }}">
                                        <i class="fa-light fa-language me-1"></i> {{ $exam->language->name ?? '-' }}
                                    </a>
                                </div>
                            </div>
                            <h5 class="h2_course-content-title mb-1">
                                <a href="{{ route('frontend.exam.detail', $exam->code) }}">{{ $exam->name }}</a>
                            </h5>
                            <p class="my-1 text-muted">
                                @php $secondToTime = secondToTime($exam->duration); @endphp
                                {{ __('Süre') }}: {{ formatSecondToTime($secondToTime) }} (<span class="text-muted">{{ $secondToTime }}</span>)
                            </p>
                        </div>
                        <div class="h2_course-content-bottom">
                            <span>
                                <i class="fa-light fa-table-columns me-1"></i>
                                {{ $exam->sections_with_no_parent_count }} {{ __('Bölüm') }}
                            </span>
                            <span>
                                <i class="fa-light fa-question-circle me-1"></i>
                                {{ $exam->questions_count }} {{ __('Soru') }}
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
            <strong class="mx-2">{{ __('Gösterilecek sonuç bulunamadı!') }}</strong>
        </div>
    @endif

</div>

