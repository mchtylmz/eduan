@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverFaq
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverFaq])
@endpush
@section('content')

    <!-- faq area start -->
    <div class="h4_faq-area pt-30 pb-75">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="h4_faq-wrap mb-10">
                        @if(!empty($page->content) && data_get($page->content, 'faqStatus') == \App\Enums\StatusEnum::ACTIVE->value)
                            <div class="section-area-4 mb-30 text-center">
                                <p class="section-text">
                                    {!! data_get($page->content, 'faqDescription') !!}
                                </p>
                            </div>
                        @endif
                        <div class="h4_faq-content">
                            @livewire('frontend.faqs.list-faqs', ['limit' => 50])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- faq area end -->

@endsection
