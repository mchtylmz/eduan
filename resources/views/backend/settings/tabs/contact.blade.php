<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <!-- row -->
    <div class="row">

        @foreach(data()->languages(active: true) as $language)
            <div class="col-12 my-3 bg-body-light p-3">
                <h5 class="mb-0">{{ str($language->code)->upper() }} - {{ $language->name }}</h5>
            </div>

            <div class="col-lg-12 mb-2">
                <label class="form-label" for="contactEmail_{{ $language->code }}">
                    {{ __('E-posta Adresi') }}
                </label>
                <input type="email"
                       class="form-control"
                       id="contactEmail_{{ $language->code }}"
                       name="settings[contactEmail_{{ $language->code }}]"
                       placeholder="{{ __('E-posta Adresi') }}.."
                       value="{{ settings()->{'contactEmail_'.$language->code} }}">
            </div>

            <div class="col-lg-6 mb-2">
                <label class="form-label" for="contactPhone1_{{ $language->code }}">
                    {{ __('Telefon Numarası (1)') }}
                </label>
                <input type="tel"
                       class="form-control"
                       id="contactPhone1_{{ $language->code }}"
                       name="settings[contactPhone1_{{ $language->code }}]"
                       placeholder="{{ __('Telefon Numarası (1)') }}.."
                       value="{{ settings()->{'contactPhone1_'.$language->code} }}">
            </div>

            <div class="col-lg-6 mb-2">
                <label class="form-label" for="contactPhone2_{{ $language->code }}">
                    {{ __('Telefon Numarası (2)') }}
                </label>
                <input type="tel"
                       class="form-control"
                       id="contactPhone2_{{ $language->code }}"
                       name="settings[contactPhone2_{{ $language->code }}]"
                       placeholder="{{ __('Telefon Numarası (2)') }}.."
                       value="{{ settings()->{'contactPhone2_'.$language->code} }}">
            </div>

            <div class="col-lg-12 mb-2">
                <label class="form-label" for="contactAddress_{{ $language->code }}">
                    {{ __('Adres') }}
                </label>
                <textarea rows="3"
                          class="form-control"
                          id="contactAddress_{{ $language->code }}"
                          name="settings[contactAddress_{{ $language->code }}]"
                          placeholder="{{ __('Adres') }}.."
                >{{ settings()->{'contactAddress_'.$language->code} }}</textarea>
            </div>
        @endforeach

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
