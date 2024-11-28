<div>
    <section class="h2_banner-area">
        <div class="h2_single-banner">
            <div class="container">
                <div class="row align-items-center justify-content-center justify-content-lg-between">
                    <div class="col-xl-6 col-lg-6 col-md-12">
                        <div class="h2_banner-content">
                            <div class="section-area-2 mb-45 ">
                                <h1 class="section-title">{{ data_get($content, 'welcomeTitle') }}</h1>
                                <p class="section-text">{{ data_get($content, 'welcomeDescription') }}</p>
                            </div>
                            @if(data_get($content, 'search', false) == \App\Enums\StatusEnum::ACTIVE->value)
                                <form action="{{ route('frontend.search') }}" method="get" class="h2_banner-form">
                                    @csrf
                                    <input type="text" placeholder="{{ __('Ara') }}..." name="search" minlength="3"
                                           required>
                                    <input type="hidden" name="tab" value="lessons"/>
                                    <button type="submit"><i class="fa-thin fa-magnifying-glass"></i></button>
                                </form>
                                <span class="h2_banner-content-text">
                                        {{ __('Çözemediğiniz herhangi bir soru var mı?') }}
                                    @guest()
                                        <a href="{{ route('login') }}">
                                            {{ __('Hemen üye olun sorunuz kalmasın') }}
                                        </a>
                                    @endguest
                                    @auth()
                                        <a href="{{ route('frontend.contact') }}">
                                            {{ __('Bize her sorunuzu sorabilirsiniz') }}
                                        </a>
                                    @endauth
                                </span>
                            @endif
                        </div>
                    </div>
                    @if($image = data_get($content, 'image'))
                        <div class="col-10 col-xl-6 col-lg-6 d-block">
                            <div class="h2_banner-right pl-80">
                                @if($image = getImage($image, false))
                                    <div class="h2_banner-img">
                                        <img src="{{ $image }}" alt="{{ data_get($content, 'welcomeTitle') }}">
                                    </div>
                                @endif
                                <div class="h2_banner-meta">
                                    <div class="h2_banner-meta-info">
                                        <h5>{{ data_get($content, 'welcomeTitle') }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
