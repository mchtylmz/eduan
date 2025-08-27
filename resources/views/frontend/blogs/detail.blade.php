@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'description' => $blog->brief,
        'keywords' => $blog->keywords,
        'image' => $blog->image
    ])
@endpush
@section('content')
    <div class="pt-4 pb-3" style="background-color: #f6f7f9 !important">
        <div class="container">
            <div class="breadcrumb-content">
                <div class="breadcrumb-list justify-content-start">
                    <a href="/">{{ __('Anasayfa') }}</a>
                    <a href="{{ route('frontend.blog') }}">{{ __('Blog') }}</a>
                    <span>{{ $title }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- blog details area start -->
    <section class="blog_details-area bg-white pt-0 pb-40">
        <div class="container">
            @if(!empty($blog->image))
                <div class="blog_details-img p-3" style="z-index: 1;">
                    <img src="{{ getImage($blog->image) }}" alt="{{ $blog->title }}" class="w-100"/>
                </div>
            @endif
            <div class="row">
                <div class="col-xl-8 col-lg-8">
                    <div class="blog_details-wrap mb-30">
                        <div class="blog_details-top mb-0 pb-3 px-0">
                            <h3 class="blog_details-title px-3">
                                {{ $blog->title }}
                            </h3>
                            <div class="blog_details-meta px-3">
                                <div class="blog_details-category">
                                    <h5 class="text-muted">
                                        <i class="fa fa-clock me-1"></i>
                                        {{ dateFormat($blog->published_at, 'd M Y') }}
                                    </h5>
                                </div>
                                <div class="blog_details-category">
                                    <h5 class="text-muted">
                                        <i class="fa fa-eye me-1"></i>
                                        {{ $blog->hits }} {{ __('Görüntüleme') }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="blog_details-content p-3">
                            {!! html_entity_decode($blog->content) !!}
                        </div>
                        <div class="blog_details-content-meta p-4 mt-3">
                            <div class="blog_details-content-tag">
                                <ul class="flex-wrap">
                                    @foreach(explode(',', $blog->keywords) as $keyword)
                                        @if(trim($keyword)) <li><a>{{ $keyword }}</a></li> @endif
                                    @endforeach
                                </ul>
                            </div>
                            <div class="blog_details-content-social">
                                <h6>{{ __('Paylaş') }}:</h6>
                                <ul>
                                    <li>
                                        <a target="_blank" href="https://twitter.com/share?text={{ $blog->title }}&url={{ route('frontend.blog.detail', $blog->slug) }}&hashtags={{ $blog->keywords }}">
                                            <i class="fa-brands fa-x"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ route('frontend.blog.detail', $blog->slug) }}">
                                            <i class="fa-brands fa-facebook-f"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a target="_blank" href="https://api.whatsapp.com/send/?text={{ $blog->title }}%20{{ route('frontend.blog.detail', $blog->slug) }}">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4">
                    <div class="blog_details-sidebar mb-20 mt-40">
                        <div class="blog_details-widget border">
                            <h4 class="blog_details-widget-title">{{ __('Popüler Bloglar') }}</h4>
                            @livewire('frontend.blogs.list-blogs', ['grid' => true, 'showHits' => true, 'paginate' => 5])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- course details area end -->
@endsection
