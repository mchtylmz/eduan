<div>
    <section class="h10_category-area home-catalog home-catalog-lessons bg-white">
        <div class="container">
            <header class="home-catalog-heading">
                <div>
                    <h2 class="section-title">{{ data_get($content, 'lessonTitle') }}</h2>
                    <p class="section-text">{{ data_get($content, 'lessonDescription') }}</p>
                </div>
                <a href="{{ route('frontend.lessons') }}" class="home-catalog-link">{{ __('learning.home_lessons') }} <i class="fa-light fa-arrow-right" aria-hidden="true"></i></a>
            </header>
            <div class="row">
                @livewire('frontend.lessons.list-lessons', [
                    'showHits' => true,
                    'showPaginate' => false,
                    'paginate' => intval(data_get($content, 'lessonCount', 12)),
                    'showSearch' => false,
                    'home' => true
                ])
            </div>
        </div>
    </section>
</div>
