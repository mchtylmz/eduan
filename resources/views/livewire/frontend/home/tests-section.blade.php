<div>
    <section class="h2_course-area home-catalog home-catalog-tests">
        <div class="container">
            <header class="home-catalog-heading">
                <div>
                    <h2 class="section-title">{{ data_get($content, 'testTitle') }}</h2>
                    <p class="section-text">{{ data_get($content, 'testDescription') }}</p>
                </div>
                <a href="{{ route('frontend.tests') }}" class="home-catalog-link">{{ __('learning.home_tests') }} <i class="fa-light fa-arrow-right" aria-hidden="true"></i></a>
            </header>
            <div class="h2_course-wrap">
                @livewire('frontend.tests.list-tests', [
                     'showHits' => true,
                     'showPaginate' => false,
                     'paginate' => intval(data_get($content, 'testCount', 12)),
                     'showSearch' => false
                 ])
            </div>

        </div>
    </section>
</div>
