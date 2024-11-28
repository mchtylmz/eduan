<div>
    @if($showSearch && !$visibilityLite)
        <div class="innerPage_course-top mb-30">
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-4 col-md-4 mb-0">
                    @if($search && $word)
                        <div class="btn-group mb-3" role="group">
                            <button type="button" class="btn btn-secondary" disabled>{{ __('Ara') }}
                                : {{ $word }}</button>
                            <button type="button" wire:click="clearSearch" class="btn btn-danger"><i
                                    class="fa fa-times"></i></button>
                        </div>
                    @endif
                </div>
                <div class="col-xl-8 col-md-8">
                    <div class="innerPage_course-right mb-20">
                        <div class="innerPage_course-search">
                            <form wire:submit.prevent="searchLesson" novalidate>
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

    @if(count($lessons = $this->lessons()))
        <div class="row">
            @foreach($lessons as $lesson)
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <div class="h10_category-item mb-30 h10_category_bg-{{ $lesson->id %10 }}">
                        <h4 class="h10_category-item-title">
                            <a href="{{ route('frontend.lesson', $lesson->code) }}">
                                {{ $lesson->name }}
                            </a>
                        </h4>
                        <p class="my-2 text-muted">
                            <a href="{{ route('frontend.lesson', $lesson->code) }}">
                                {{ $lesson->description }}
                            </a>
                        </p>
                        <div class="d-flex align-items-center justify-content-between">
                            <a href="{{ route('frontend.lesson', $lesson->code) }}">
                                <p>
                                    <i class="fa-light fa-book-alt mx-1"></i>
                                    <span>{{ $lesson->topics_count ?? 0 }} {{ __('Konu') }}</span>
                                </p>
                            </a>
                            <p>
                                <i class="fa-light fa-pen mx-1"></i>
                                <span>{{ $lesson->exams_count ?? 0 }} {{ __('Test') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach

            @if($showPaginate)
                <div class="col-12">
                    {{ $lessons->links('components.frontend.livewire-pagination') }}
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
