<div id="install-prompt" class="box-icon" style="display: none;">
    <span id="install-button" class="circle">
        <img src="{{ asset($siteFavicon) }}" alt="Uygulama Yükle">
    </span>
</div>

<footer class="footer-area h6_footer-area">
    <div class="footer-top pt-55 pb-2">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-xl-3 col-lg-7 col-md-7 col-sm-12">
                    <div class="footer-widget mb-20">
                        <div class="footer-logo">
                            @includeIf('frontend.layouts.section.footer-logo', ['mw' => true])
                        </div>
                        <p class="footer-widget-text mb-35">
                            {{ settingLocale('siteDescription') }}
                        </p>
                        <div class="footer-social">
                            @includeIf('frontend.layouts.section.social-links')
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-5 col-md-5 col-sm-6 d-flex justify-content-xl-center">
                    <div class="footer-widget mb-20">
                        <h5 class="footer-widget-title">{{ __('Hızlı Linkler') }}</h5>
                        <div class="footer-widget-list">
                            <ul>
                                @if($pages = data()->footerPages())
                                    @foreach($pages as $page)
                                        <li>
                                            <a class="py-2" href="{{ route('frontend.page', $page->slug) }}">{{ $page->title }}</a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-5 col-md-5 col-sm-6 d-flex justify-content-xl-center order-md-4 order-xl-3">
                    <div class="footer-widget mb-20">
                        <h5 class="footer-widget-title">{{ __('Konular') }}</h5>
                        <div class="footer-widget-list">
                            <ul>
                                @if($topics = data()->topics(hits: true, limit: 6))
                                    @foreach($topics as $topic)
                                        <li>
                                            <a class="py-2" href="{{ route('frontend.tests', $topic->code) }}">{{ $topic->title }}</a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-7 col-md-7 col-sm-12 order-md-3 order-xl-4">
                    <div class="footer-widget ml-80 mb-20">
                        <h5 class="footer-widget-title">{{ __('Bilgilendirme Aboneliği') }}</h5>
                        <p class="footer-widget-text mb-20 newsletter-text">{{ __('Yeni eklenen konulardan, testlerden haberdar olmak için kayıt olabilirsiniz.') }}</p>
                        @livewire('frontend.newsletter.newsletter-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="copyright-area">
        <div class="container pb-3">
            <div class="row justify-content-between">
                <div class="col-sm-9">
                    <div class="copyright-text">
                        <p class="text-center text-sm-start">
                            {{ settingLocale('siteTitle') }} © {{ now()->year }} | {{ __('Tüm Hakları Saklıdır.') }}
                        </p>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="copyright-text">
                        <p class="text-center text-sm-end">
                            <span>{{ __('Dil') }}:</span>
                            <span class="text-capitalize">{{ data()->language(app()->getLocale())?->name }}</span>
                            <span class="text-uppercase">({{ app()->getLocale() }})</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
