<div>
    @if(count($blogs = $this->blogs()))
        <div class="row">
            @foreach($blogs as $blog)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="h2_blog-item mb-20 border">
                        @if(!empty($blog->image))
                            <div class="h2_blog-img">
                                <a href="{{ route('frontend.blog.detail', $blog->slug) }}"><img
                                        src="{{ getImage($blog->image) }}" alt="{{ $blog->title }}"></a>
                            </div>
                        @endif
                        <div class="h2_blog-content">
                            <div class="h2_blog-content-meta">
                                <span><i class="fa-thin fa-clock"></i>{{ dateFormat($blog->published_at, 'd M Y') }}</span>
                                <span><i class="fa-thin fa-eye"></i>{{ $blog->hits }} {{ __('Görüntüleme') }}</span>
                            </div>
                            <h5 class="h2_blog-content-title mb-2">
                                <a href="{{ route('frontend.blog.detail', $blog->slug) }}">{{ $blog->title }}</a>
                            </h5>
                            <p class="mb-3">
                                <a href="{{ route('frontend.blog.detail', $blog->slug) }}">
                                    {{ str($blog->brief)->limit(60) }}
                                </a>
                            </p>
                            <a href="{{ route('frontend.blog.detail', $blog->slug) }}"
                               class="theme-btn blog-btn t-theme-btn">
                                {{ __('Devamını Oku') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($showPaginate)
            <div class="text-center">
                {{ $blogs->links('components.frontend.livewire-pagination') }}
            </div>
        @endif

    @else
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fa fa-exclamation-circle mx-2"></i>
            <strong class="mx-2">{{ __('Gösterilecek sonuç bulunamadı!') }}</strong>
        </div>
    @endif
</div>
