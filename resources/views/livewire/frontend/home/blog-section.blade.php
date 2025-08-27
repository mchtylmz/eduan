<div>
    <section class="h10_category-area pt-75 pb-25 bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6">
                    <div class="section-area-10 mb-55 text-center">
                        <h2 class="section-title mb-20">{{ data_get($content, 'blogTitle') }}</h2>
                        <p class="section-text">{{ data_get($content, 'blogDescription') }}</p>
                    </div>
                </div>
            </div>

            @livewire('frontend.blogs.list-blogs', ['grid' => false, 'paginate' => intval(data_get($content, 'blogCount', 3)), 'showPaginate' => false])
        </div>
    </section>
</div>
