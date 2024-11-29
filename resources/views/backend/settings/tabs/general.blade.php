<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <div class="mb-3">
        <label class="form-label" for="appName">{{ __('Proje Adı') }}</label>
        <input type="text" class="form-control" id="appName" name="settings[appName]" placeholder="{{ __('Proje Adı') }}.." value="{{ settings()->appName }}" required>
    </div>
    <div class="row">
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="timezone">{{ __('Saat Dilimi') }}</label>
            <select id="timezone" class="form-control selectpicker" data-live-search="true" data-size="10" name="settings[timezone]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(timezone_identifiers_list() as $timezone)
                    <option value="{{ $timezone }}" @selected($timezone == settings()->timezone)>{{ $timezone }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="registerStatus">{{ __('Kayıt Olma Özelliği') }}</label>
            <select id="registerStatus" class="form-control" name="settings[registerStatus]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(\App\Enums\StatusEnum::options() as $key => $name)
                    <option value="{{ $key }}" @selected($key == settings()->registerStatus)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="multiLanguage">{{ __('Çoklu Dil Özelliği') }}</label>
            <select id="multiLanguage" class="form-control" name="settings[multiLanguage]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(\App\Enums\StatusEnum::options() as $key => $name)
                    <option value="{{ $key }}" @selected($key == settings()->multiLanguage)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="defaultLocale">{{ __('Varsayılan Dil') }}</label>
            <select id="defaultLocale" class="form-control" name="settings[defaultLocale]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach($languages = data()->languages(active: true) as $locale)
                    <option value="{{ $locale->code }}" @selected($locale->code == settings()->defaultLocale)>{{ str($locale->code)->upper() }} - {{ $locale->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="floatWhatsapp">{{ __('Kayan Whatsapp Butonu') }}</label>
            <select id="floatWhatsapp" class="form-control" name="settings[floatWhatsapp]">
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(\App\Enums\StatusEnum::options() as $key => $name)
                    <option value="{{ $key }}" @selected($key == settings()->floatWhatsapp)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="privacyPage">{{ __('Site Kuralları ve Gizlilik Politikası Sayfası') }}</label>
            <select id="privacyPage" class="form-control" name="settings[privacyPage]">
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(\App\Models\Page::all() as $page)
                    <option value="{{ $page->id }}" @selected($page->id == settings()->privacyPage)>{{ $page->title }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- row -->
    <div class="row">

        @foreach($languages as $language)
            <div class="col-12 my-3 bg-body-light p-3">
                <h5 class="mb-0">{{ str($language->code)->upper() }} - {{ $language->name }}</h5>
            </div>

            <div class="col-lg-6 mb-1">
                <label class="form-label" for="siteTitle_{{ $language->code }}">
                    {{ __('Site Başlığı') }}
                </label>
                <input type="text"
                       class="form-control"
                       id="siteTitle_{{ $language->code }}"
                       name="settings[siteTitle_{{ $language->code }}]"
                       placeholder="{{ __('Site Başlığı') }}.."
                       value="{{ settings()->{'siteTitle_'.$language->code} }}"
                       required>
            </div>

            <div class="col-lg-6 mb-1">
                <label class="form-label" for="siteDescription_{{ $language->code }}">
                    {{ __('Site Açıklaması') }}
                </label>
                <textarea rows="2"
                          class="form-control"
                          id="siteDescription_{{ $language->code }}"
                          name="settings[siteDescription_{{ $language->code }}]"
                          placeholder="{{ __('Site Açıklaması') }}.."
                >{{ settings()->{'siteDescription_'.$language->code} }}</textarea>
            </div>

            <div class="col-lg-12 mb-1">
                <label class="form-label" for="siteKeywords_{{ $language->code }}">
                    {{ __('Site Anahtar Kelimeleri') }}
                </label>
                <textarea rows="2"
                          class="form-control"
                          id="siteKeywords_{{ $language->code }}"
                          name="settings[siteKeywords_{{ $language->code }}]"
                          placeholder="{{ __('Site Anahtar Kelimeleri') }}.."
                >{{ settings()->{'siteKeywords_'.$language->code} }}</textarea>
                <small>{{ __('Anahtar kelimeleri virgül ile ayırarak yazılabilir.') }}</small>
            </div>

            <div class="col-lg-12 mb-1">
                <label class="form-label" for="socialWhatsappText_{{ $language->code }}">
                    {{ __('Whatsapp Mesaj Metni') }}
                </label>
                <textarea rows="1"
                          class="form-control"
                          id="socialWhatsappText_{{ $language->code }}"
                          name="settings[socialWhatsappText_{{ $language->code }}]"
                          placeholder="{{ __('Whatsapp Mesaj Metni') }}.."
                >{{ settings()->{'socialWhatsappText_'.$language->code} ?? '' }}</textarea>
            </div>
        @endforeach

        <div class="col-12 my-3 bg-body-light p-3">
            <h5 class="mb-0">{{ __('Sosyal Medya') }}</h5>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="socialFacebook">{{ __('Facebook') }}</label>
                <div class="input-group">
                    <span class="input-group-text">facebook.com/</span>
                    <input type="text" class="form-control" id="socialFacebook" name="settings[socialFacebook]" placeholder="{{ __('Facebook') }}.." value="{{ settings()->socialFacebook ?? '' }}">
                    <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="socialX">{{ __('Twitter (X)') }}</label>
                <div class="input-group">
                    <span class="input-group-text">twitter.com/</span>
                    <input type="text" class="form-control" id="socialX" name="settings[socialX]" placeholder="{{ __('Twitter') }}.." value="{{ settings()->socialX ?? '' }}">
                    <span class="input-group-text"><i class="fab fa-x"></i></span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="socialInstagram">{{ __('Instagram') }}</label>
                <div class="input-group">
                    <span class="input-group-text">instgram.com/</span>
                    <input type="text" class="form-control" id="socialInstagram" name="settings[socialInstagram]" placeholder="{{ __('Instagram') }}.." value="{{ settings()->socialInstagram ?? '' }}">
                    <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="socialYoutube">{{ __('Youtube') }}</label>
                <div class="input-group">
                    <span class="input-group-text">youtube.com/</span>
                    <input type="text" class="form-control" id="socialYoutube" name="settings[socialYoutube]" placeholder="{{ __('Youtube') }}.." value="{{ settings()->socialYoutube ?? '' }}">
                    <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="socialWhatsapp">{{ __('Whatsapp') }}</label>
                <div class="input-group">
                    <span class="input-group-text">wa.me/</span>
                    <input type="text" class="form-control" id="socialWhatsapp" name="settings[socialWhatsapp]" placeholder="905XXXXXXXXX.." value="{{ settings()->socialWhatsapp ?? '' }}">
                    <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                </div>
            </div>
        </div>

    </div>
    <!-- row -->

    @can('settings:update')
        <div class="mb-3 text-center py-2 mt-3">
            <button type="submit" class="btn btn-alt-primary px-4">
                <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
            </button>
        </div>
    @endcan
</form>
<!-- form -->
