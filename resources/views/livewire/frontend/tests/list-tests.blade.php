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
                        @if(empty($topic_id))
                            <div class="innerPage_course-category" wire:ignore>
                                <select wire:model.change="lesson_id"
                                        class="innerPage_course-category-list selectpicker"
                                        data-live-search="true"
                                        data-size="6">
                                    <option value="">{{ __('Tüm Dersler') }}</option>
                                    @foreach(data()->lessons(limit: 50) as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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

    @if(count($tests = $this->tests()))
        <div class="row">
            @foreach($tests as $test)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="h2_course-item mb-20 border {{ auth()->check() && count($test->userResults) ? 'border-danger' : '' }}">
                        <div class="h2_course-content pb-4">
                            <div class="h2_course-content-top">
                                <div class="h2_course-rating">
                                    <a href="{{ route('frontend.test.detail', $test->code) }}">
                                        <i class="fa-light fa-language me-1"></i> {{ $test->language->name ?? '-' }}
                                    </a>
                                </div>
                                <div class="h2_course-save">
                                    <a type="button"
                                       @class(['bookmark', 'active' => in_array($test->id, $userFavoritesTests)])
                                       wire:click="toggleBookmark({{ $test->id }})"
                                       title="{{ __('Favoriye Ekle') }}">
                                        <i class="fa fa-bookmark{{ in_array($test->id, $userFavoritesTests) ? '-slash':'' }}"></i>
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
                                <span>
                                    <i class="fa-light fa-question-circle me-1"></i>
                                    {{ $test->questions_count }} {{ __('Soru') }}
                                </span>
                                <span>
                                    <i class="fa-light fa-comments me-1"></i>
                                    {{ $test->reviews_count }}
                                </span>
                            @if(auth()->check() && count($test->userResults))
                                <span>
                                    <i class="fa-light fa-poll me-1"></i>
                                    {{ collect($test->userResults)->sum('correct_count') }} {{ __('Doğru') }}
                                    /
                                    {{ collect($test->userResults)->sum('incorrect_count') }} {{ __('Yanlış') }}
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

