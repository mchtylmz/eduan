<div>
    <section class="h2_course-area pt-75 pb-75">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6">
                    <div class="section-area-10 mb-55 text-center">
                        <h1 class="section-title">{{ data_get($content, 'testTitle') }}</h1>
                        <p class="section-text">{{ data_get($content, 'testDescription') }}</p>
                    </div>
                </div>
            </div>
            <div class="h2_course-wrap">
                @livewire('frontend.tests.list-tests', [
                     'showHits' => true,
                     'showPaginate' => false,
                     'paginate' => intval(data_get($content, 'testCount', 12)),
                     'showSearch' => false
                 ])
            </div>

            <div class="col-12">
                <div class="h10_category-item-btn pt-30">
                    <a href="{{ route('frontend.tests') }}">{{ __('Tüm Testler') }} <i class="fa-light fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>
</div>
