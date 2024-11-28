<div>
    <section class="h10_category-area pt-75 pb-75 bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6">
                    <div class="section-area-10 mb-55 text-center">
                        <h2 class="section-title mb-20">{{ data_get($content, 'lessonTitle') }}</h2>
                        <p class="section-text">{{ data_get($content, 'lessonDescription') }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                @livewire('frontend.lessons.list-lessons', [
                    'showHits' => true,
                    'showPaginate' => false,
                    'paginate' => intval(data_get($content, 'lessonCount', 12)),
                    'showSearch' => false
                ])
                <div class="col-12">
                    <div class="h10_category-item-btn pt-30">
                        <a href="{{ route('frontend.lessons') }}">
                            {{ __('Tüm Dersler') }} <i class="fa-light fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
