@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverContact
    ])
@endpush
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverContact])
@endpush
@section('content')

    <!-- contact area start -->
    <section class="contact-area pt-30">
        <div class="container">
            <div class="contact-wrap">
                <div class="row">
                    <div class="col-xl-8 col-md-8 order-2 order-sm-1">
                        <div class="contact-content pr-40 mb-40 pt-5 pt-sm-0">
                            <h3 class="contact-title mb-25">{{ __('Bize Mesaj Gönder') }}</h3>
                            @livewire('frontend.contacts.contact-form')
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4 order-1 order-sm-2">
                        <div class="contact-info ml-50 mb-20">
                            <h3 class="contact-title mb-40">{{ __('Bize Ulaşın') }}</h3>

                            @if($address = settingLocale('contactAddress'))
                                <div class="contact-info-item">
                                    <span><i class="fa-thin fa-location-dot"></i>{{ __('Adres') }}</span>
                                    <p>{!! $address !!}</p>
                                </div>
                            @endif

                            @if(settingLocale('contactPhone1') || settingLocale('contactPhone2'))
                            <div class="contact-info-item">
                                <span><i class="fa-thin fa-mobile-notch"></i>{{ __('Telefon numarası') }}</span>
                                @if($phone = settingLocale('contactPhone1'))
                                    <a href="tel:{{ str_replace(['(',')',' '], '', $phone) }}">{{ $phone }}</a>
                                @endif
                                @if($phone = settingLocale('contactPhone2'))
                                    <a href="tel:{{ str_replace(['(',')',' '], '', $phone) }}">{{ $phone }}</a>
                                @endif
                            </div>
                            @endif

                            @if($email = settingLocale('contactEmail'))
                            <div class="contact-info-item">
                                <span><i class="fa-thin fa-envelope"></i>{{ __('E-posta Adresi') }}</span>
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            </div>
                            @endif

                            <div class="contact-social">
                                <span>{{ __('Sosyal Medya') }}</span>
                                @includeIf('frontend.layouts.section.social-links', ['active' => true])
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- contact area end -->

@endsection
