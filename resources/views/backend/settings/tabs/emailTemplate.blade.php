<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <div class="block block-rounded">
        <ul class="nav nav-tabs nav-tabs-alt" role="tablist">
            @foreach($languages = data()->languages(active: true) as $locale)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ $loop->index == 0 ? 'active': '' }}"
                            id="email-template-{{ $locale->code }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#email-template-{{ $locale->code }}"
                            role="tab"
                            aria-controls="email-template-{{ $locale->code }}"
                            aria-selected="{{ $loop->index ? 'true': 'false' }}">
                        {{ str($locale->code)->upper() }} - {{ $locale->name }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="block-content tab-content">
            @foreach($languages = data()->languages(active: true) as $locale)
                <div class="tab-pane row py-1 {{ $loop->index == 0 ? 'active show': '' }}"
                     id="email-template-{{ $locale->code }}"
                     role="tabpanel"
                     aria-labelledby="email-template-{{ $locale->code }}"
                     tabindex="0">
                    <div class="col-12 mb-3 bg-body-light p-3">
                        <h5 class="mb-0">{{ __('Üyelik Onaylama ') }}</h5>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label" for="emailVerificationSubject_{{ $locale->code }}">
                            {{ __('E-posta Konusu') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="emailVerificationSubject_{{ $locale->code }}"
                               name="settings[emailVerificationSubject_{{ $locale->code }}]"
                               placeholder="{{ __('E-posta Konusu') }}.."
                               value="{{ settings()->{'emailVerificationSubject_'.$locale->code} }}"
                               required>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label">
                            {{ __('E-posta İçeriği') }}
                        </label>
                        <p class="my-1 border px-3">
                            {{ __('Kullanıcı Parametreleri') }}:
                            <span class="py-1 px-2 mx-1">[ad]</span>
                            <span class="py-1 px-2 mx-1">[soyad]</span>
                            <span class="py-1 px-2 mx-1">[ad_soyad]</span>
                            <span class="py-1 px-2 mx-1">[email]</span>
                            <span class="py-1 px-2 mx-1">[telefon]</span>
                        </p>
                        <x-tinymce.editor :livewire="false"
                                          height="300"
                                          name="settings[emailVerificationContent_{{ $locale->code }}]"
                                          value="{{ settings()->{'emailVerificationContent_'.$locale->code} }}"/>
                    </div>

                    <hr>

                    <div class="col-12 mb-3 bg-body-light p-3">
                        <h5 class="mb-0">{{ __('Parola Unuttum ') }}</h5>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label" for="emailRecoverPasswordSubject_{{ $locale->code }}">
                            {{ __('E-posta Konusu') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="emailRecoverPasswordSubject_{{ $locale->code }}"
                               name="settings[emailRecoverPasswordSubject_{{ $locale->code }}]"
                               placeholder="{{ __('E-posta Konusu') }}.."
                               value="{{ settings()->{'emailRecoverPasswordSubject_'.$locale->code} }}"
                               required>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label">
                            {{ __('E-posta İçeriği') }}
                        </label>
                        <p class="my-1 border px-3">
                            {{ __('Kullanıcı Parametreleri') }}:
                            <span class="py-1 px-2 mx-1">[ad]</span>
                            <span class="py-1 px-2 mx-1">[soyad]</span>
                            <span class="py-1 px-2 mx-1">[ad_soyad]</span>
                            <span class="py-1 px-2 mx-1">[email]</span>
                            <span class="py-1 px-2 mx-1">[telefon]</span>
                        </p>
                        <x-tinymce.editor :livewire="false"
                                          height="300"
                                          name="settings[emailRecoverPasswordContent_{{ $locale->code }}]"
                                          value="{{ settings()->{'emailRecoverPasswordContent_'.$locale->code} }}"/>
                    </div>

                    <hr>

                    <div class="col-12 mb-3 bg-body-light p-3">
                        <h5 class="mb-0">{{ __('Yeni Parola Oluşturduktan Sonra ') }}</h5>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label" for="emailAfterCreatePasswordSubject_{{ $locale->code }}">
                            {{ __('E-posta Konusu') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="emailAfterCreatePasswordSubject_{{ $locale->code }}"
                               name="settings[emailAfterCreatePasswordSubject_{{ $locale->code }}]"
                               placeholder="{{ __('E-posta Konusu') }}.."
                               value="{{ settings()->{'emailAfterCreatePasswordSubject_'.$locale->code} }}"
                               required>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label">
                            {{ __('E-posta İçeriği') }}
                        </label>
                        <p class="my-1 border px-3">
                            {{ __('Kullanıcı Parametreleri') }}:
                            <span class="py-1 px-2 mx-1">[ad]</span>
                            <span class="py-1 px-2 mx-1">[soyad]</span>
                            <span class="py-1 px-2 mx-1">[ad_soyad]</span>
                            <span class="py-1 px-2 mx-1">[email]</span>
                            <span class="py-1 px-2 mx-1">[telefon]</span>
                        </p>
                        <x-tinymce.editor :livewire="false"
                                          height="300"
                                          name="settings[emailAfterCreatePasswordContent_{{ $locale->code }}]"
                                          value="{{ settings()->{'emailAfterCreatePasswordContent_'.$locale->code} }}"/>
                    </div>


                </div>
            @endforeach
        </div>
    </div>

    @can('settings:update')
        <div class="mb-3 text-center py-2 mt-3">
            <button type="submit" class="btn btn-alt-primary px-4">
                <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
            </button>
        </div>
    @endcan
</form>
<!-- form -->
