<div>
    @if(!$visibilityLite)
        <div class="innerPage_course-top mb-30">
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-4 col-md-4 mb-0">
                    @if($search && $word)
                        <div class="btn-group mb-3" role="group">
                            <button type="button" class="btn btn-secondary" disabled>{{ __('Ara') }}: {{ $word }}</button>
                            <button type="button" wire:click="clearSearch" class="btn btn-danger"><i class="fa fa-times"></i></button>
                        </div>
                    @endif
                </div>
                <div class="col-xl-8 col-md-8">
                    <div class="innerPage_course-right mb-20">
                        <div class="innerPage_course-search">
                            <form wire:submit.prevent="searchTopics" novalidate>
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

    @if(count($topics = $this->topics()))
        <div class="row">
            @foreach($topics as $topic)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="h2_course-item mb-20 border">
                        <div class="h2_course-content">
                            <div class="h2_course-content-info">
                                <span>{{ $topic->lesson->name }}</span>
                            </div>
                            <h5 class="h2_course-content-title">
                                <a href="{{ route('frontend.tests', $topic->code) }}">{{ $topic->title }}</a>
                            </h5>
                            <p class="h2_course-content-text mb-0">
                                <a href="{{ route('frontend.tests', $topic->code) }}">
                                    {{ $topic->description }}
                                </a>
                            </p>
                        </div>
                        <div class="h2_course-content-bottom">
                            <a href="{{ route('frontend.tests', $topic->code) }}">
                                <span>
                                    <i class="fa-light fa-question-circle me-2"></i>
                                    {{ count($topic->exams) }} {{ __('Test') }}
                                </span>
                            </a>
                            @if(auth()->check())
                                <span>
                                    <i class="fa-light fa-poll me-2"></i>
                                    <span>{{ $topic->userResults()->count() }}</span>
                                    <span>/</span>
                                    <span>{{ count($topic->exams) }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12">
                {{ $topics->links('components.frontend.livewire-pagination') }}
            </div>
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fa fa-exclamation-circle mx-2"></i>
            <strong class="mx-2">{{ __('Gösterilecek sonuç bulunamadı!') }}</strong>
        </div>
    @endif
</div>
