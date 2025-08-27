@extends('frontend.layouts.app')
@section('content')
    <!-- banner area start -->
    @if(!empty($page->content) && (data_get($page->content, 'welcomeTitle') || data_get($page->content, 'search')))
        @livewire('frontend.home.welcome-section', ['page' => $page])
    @endif
    <!-- banner area end -->

    <!-- lessons area start -->
    @if(!empty($page->content) && data_get($page->content, 'lessonStatus') == \App\Enums\StatusEnum::ACTIVE->value)
        @livewire('frontend.home.lessons-section', ['page' => $page])
    @endif
    <!-- lessons area end -->

    <!-- course area start -->
    @if(!empty($page->content) && data_get($page->content, 'testStatus') == \App\Enums\StatusEnum::ACTIVE->value)
        @livewire('frontend.home.tests-section', ['page' => $page])
    @endif
    <!-- course area end -->

    <!-- blog area start -->
    @if(!empty($page->content) && data_get($page->content, 'blogStatus') == \App\Enums\StatusEnum::ACTIVE->value)
        @livewire('frontend.home.blog-section', ['page' => $page])
    @endif
    <!-- blog area end -->

    <!-- faq area start -->
    @if(!empty($page->content) && data_get($page->content, 'faqStatus') == \App\Enums\StatusEnum::ACTIVE->value)
        @livewire('frontend.home.faq-section', ['page' => $page])
    @endif
    <!-- faq area end -->

@endsection
