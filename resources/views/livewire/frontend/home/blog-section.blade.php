<div>
    <section class="h10_category-area home-catalog home-catalog-blog bg-white">
        <div class="container">
            <header class="home-catalog-heading">
                <div>
                    <h2 class="section-title">{{ data_get($content, 'blogTitle') }}</h2>
                    <p class="section-text">{{ data_get($content, 'blogDescription') }}</p>
                </div>
                <a href="{{ route('frontend.blog') }}" class="home-catalog-link">{{ __('learning.home_blogs') }} <i class="fa-light fa-arrow-right" aria-hidden="true"></i></a>
            </header>

            @livewire('frontend.blogs.list-blogs', ['grid' => false, 'paginate' => intval(data_get($content, 'blogCount', 3)), 'showPaginate' => false])
        </div>
    </section>
</div>
