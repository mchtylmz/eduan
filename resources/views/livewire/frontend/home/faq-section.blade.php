<div>
    <div class="h4_faq-area pt-75 pb-75">
        <div class="container">
            <div class="row align-items-center">
                @if($image = data_get($content, 'faqImage'))
                    <div class="col-lg-6">
                        @if($image = getImage($image, false))
                            <div class="h4_faq-img w_img mb-50">
                                <img src="{{ $image }}" alt="{{ data_get($content, 'faqTitle') }}">
                            </div>
                        @endif
                    </div>
                @endif
                <div class="col-lg-6">
                    <div class="h4_faq-wrap mb-10">
                        <div class="section-area-4 mb-30 text-center">
                            <h2 class="section-title mb-10">{{ data_get($content, 'faqTitle') }}</h2>
                            <p class="section-text">
                                {!! data_get($content, 'faqDescription') !!}
                            </p>
                        </div>
                        <div class="h4_faq-content">
                            @livewire('frontend.faqs.list-faqs', ['limit' => intval(data_get($content, 'faqCount', 4))])
                        </div>
                    </div>
                    <div class="h10_category-item-btn pt-10 justify-content-end">
                        <a href="{{ route('frontend.faqs') }}">
                            {{ __('Sıkça Sorulan Sorular') }} <i class="fa-light fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
