@extends('frontend.layouts.app')
@section('content')
    <section class="contact-area pt-30 pb-30">
        <div class="container">
            @if(agentDevice() == 'desktop')
                <h4>💻 {{ __(" Chrome Tarayıcısında “Uygulama Olarak Yükle” Adımları") }}</h4>
                <div class="row mb-3">
                    <div class="col-lg-6 col-12 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('1. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/browser_1.png') }}"
                                 alt="{{ __('1. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/browser_1.png') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('2. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/browser_2.png') }}"
                                 alt="{{ __('2. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/browser_2.png') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                </div>
                <ol class="list-group list-group-numbered mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Web sitesine giriş yap') }}</div>
                            {{ __('Chrome ile yüklemek istediğin web sitesini aç  (örneğin https://hypotenuse.be)') }}
                            <br>
                            📸 {{ __('Bkz: 1. görsel – sitenin açık olduğu hali.') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Adres çubuğunun sağındaki install/app (yükle) simgesine tıkla') }}</div>
                            {{ __('Adres çubuğunun sağ üst köşesinde bir bilgisayar ekranı + ok şeklinde bir simge belirecek.') }}
                            <br>
                            {{ __('Bu simgeye tıkladığında "Uygulamayı yükle" anlamına gelen bir pencere açılır.') }}
                            <br>
                            📸 {{ __('Bkz: 2. görsel – "Install app" popup') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Açılan pencerede “Yükle / Install” butonuna bas') }}</div>
                            {{ __('Karşına çıkan kutucukta sitenin adı ve kısa yolu görünecek.') }}
                            <br>
                            {{ __('Buradaki Install (veya Türkçe’de "Yükle") düğmesine tıklayarak siteyi uygulama gibi yükleyebilirsin.') }}
                        </div>
                    </li>
                </ol>
            @endif

            @if(agentDevice() == 'ios')
                <h4>📱 {{ __("iPhone'da “Ana Ekrana Ekle” Adımları") }}</h4>
                <div class="row mb-3">
                    <div class="col-lg-4 col-6 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('1. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/ios_1.jpeg') }}"
                                 alt="{{ __('1. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/ios_1.jpeg') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('2. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/ios_2.jpeg') }}"
                                 alt="{{ __('2. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/ios_2.jpeg') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-12 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('3. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/ios_3.jpeg') }}"
                                 alt="{{ __('3. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/ios_3.jpeg') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                </div>
                <ol class="list-group list-group-numbered mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Safari ile :site sitesine gir', ['site' => url('/')]) }}</div>
                            {{ __('Uygulama gibi eklemek istediğin siteyi (örneğin https://hypotenuse.be) Safari tarayıcısında aç.') }}
                            <br>
                            📸 {{ __('Bkz: 3. görsel') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Paylaş simgesine dokun') }}</div>
                            {{ __('Ekranın alt orta kısmındaki yukarı ok işaretli kare (paylaş simgesi) butonuna dokun.') }}
                            <br>
                            📸 {{ __('Bkz: 3. görsel') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Aşağı kaydırarak “Ana Ekrana Ekle” seçeneğini bul') }}</div>
                            {{ __('Açılan menüde biraz aşağı kaydır.') }}
                            <br>
                            {{ __('“Add to Home Screen” / “Ana Ekrana Ekle” seçeneğini göreceksin.') }}
                            <br>
                            {{ __('Bu seçeneğe dokun.') }}
                            <br>
                            📸 {{ __('Bkz: 2. görsel') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('İsim gir ve “Ekle”ye dokun') }}</div>
                            {{ __('Açılan ekranda site için bir ad yazabilir (örneğin: "Hypotenuse") ve sağ üstteki “Add / Ekle” butonuna basabilirsin.') }}
                            <br>
                            📸 {{ __('Bkz: 1. görsel') }}
                        </div>
                    </li>
                </ol>
            @endif

            @if(agentDevice() == 'android')
                <h4 class="mt-3">📱 {{ __("Android Cihazlarda “Ana Ekrana Ekle” Adımları") }}</h4>
                <div class="row mb-3">
                    <div class="col-lg-4 col-6 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('1. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/android_1.jpeg') }}"
                                 alt="{{ __('1. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/android_1.jpeg') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('2. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/android_2.jpeg') }}"
                                 alt="{{ __('2. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/android_2.jpeg') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-12 p-1">
                        <div class="p-2 border text-center">
                            <h5 class="text-dark fw-bold mb-1">{{ __('3. Adım') }}</h5>
                            <img class="w-100 mb-1" src="{{ asset('assets/pwa/android_3.jpeg') }}"
                                 alt="{{ __('3. Adım') }}"/>
                            <a target="_blank" href="{{ asset('assets/pwa/android_3.jpeg') }}" class="text-dark">
                                <i class="fa fa-magnifying-glass mx-1"></i> {{ __('Detaylı Görüntüle') }}
                            </a>
                        </div>
                    </div>
                </div>
                <ol class="list-group list-group-numbered mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Chrome ile :site sitesine gir', ['site' => url('/')]) }}</div>
                            {{ __('Android cihazında Google Chrome tarayıcısını aç. Uygulama gibi eklemek istediğin siteye git (örneğin: https://hypotenuse.be)') }}
                            <br>
                            📸 {{ __('Bkz: 1. görsel') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Sağ üst köşedeki üç nokta menüsüne dokun') }}</div>
                            {{ __('Chrome tarayıcısında ekranın sağ üst köşesinde bulunan “⋮” (üç nokta) simgesine tıkla.') }}
                            <br>
                            📸 {{ __('Bkz: 1. görsel') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Aşağı kaydır ve “Ana ekrana ekle” seçeneğini bul') }}</div>
                            {{ __('Açılan menüde aşağıya kaydır.') }}
                            <br>
                            {{ __('“Ana ekrana ekle” seçeneğini göreceksin. Buna dokun.') }}
                            <br>
                            📸 {{ __('Bkz: 2. görsel') }}
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold">{{ __('Kısa yol adını onayla ve “Ekle” butonuna bas') }}</div>
                            {{ __('Kısa yolun adı (örneğin: “Hypotenuse”) gösterilir. Sağ alttaki “Ekle” butonuna tıkla.') }}
                            <br>
                            📸 {{ __('Bkz: 3. görsel') }}
                        </div>
                    </li>
                </ol>
            @endif
        </div>
    </section>
@endsection
