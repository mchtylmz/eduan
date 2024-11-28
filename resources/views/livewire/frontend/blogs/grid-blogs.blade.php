<div>
    @if($blogs = $this->blogs())
        @foreach($blogs as $blog)
            <div class="blog_details-widget-course mb-2">
                @if(!empty($blog->image))
                    <div class="blog_details-course-img d-flex align-items-center">
                        <a href="{{ route('frontend.blog.detail', $blog->slug) }}">
                            <img src="{{ getImage($blog->image) }}" alt="{{ $blog->title }}">
                        </a>
                    </div>
                @endif

                <div class="blog_details-course-info">
                    <h6>
                        <a href="{{ route('frontend.blog.detail', $blog->slug) }}">{{ $blog->title }}</a>
                    </h6>
                    <a href="{{ route('frontend.blog.detail', $blog->slug) }}" class="inner-course-rate">
                        {{ __('Devamını Oku') }}
                    </a>
                </div>
            </div>
        @endforeach

    @else
        <div class="alert alert-warning d-flex align-items-center">
            <i class="fa fa-exclamation-circle mx-2"></i>
            <strong class="mx-2">{{ __('Gösterilecek sonuç bulunamadı!') }}</strong>
        </div>
    @endif
</div>
