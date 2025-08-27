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
                            data-bs-target="#popup-template-{{ $locale->code }}"
                            role="tab"
                            aria-controls="popup-template-{{ $locale->code }}"
                            aria-selected="{{ $loop->index ? 'true': 'false' }}">
                        {{ str($locale->code)->upper() }} - {{ $locale->name }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="block-content tab-content">
            @foreach($languages = data()->languages(active: true) as $locale)
                <div class="tab-pane row py-1 {{ $loop->index == 0 ? 'active show': '' }}"
                     id="popup-template-{{ $locale->code }}"
                     role="tabpanel"
                     aria-labelledby="popup-template-{{ $locale->code }}"
                     tabindex="0">

                    <div class="col-lg-4 mb-3">
                        <label class="form-label" for="popupStatus_{{ $locale->code }}">{{ __('Durum') }}</label>
                        <select id="popupStatus_{{ $locale->code }}" class="form-control"
                                name="settings[popupStatus_{{ $locale->code }}]" required>
                            @foreach(\App\Enums\StatusEnum::options() as $key => $name)
                                <option value="{{ $key }}" @selected($key == settings()->{'popupStatus_'.$locale->code})>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label" for="popupSubject_{{ $locale->code }}">
                            {{ __('Pop-up Başlığı') }}
                        </label>
                        <input type="text"
                               class="form-control"
                               id="popupSubject_{{ $locale->code }}"
                               name="settings[popupSubject_{{ $locale->code }}]"
                               placeholder="{{ __('Pop-up Başlığı') }}.."
                               value="{{ settings()->{'popupSubject_'.$locale->code} }}"
                        />
                    </div>

                    <div class="col-lg-12 mb-2">
                        <label class="form-label">{{ __('Pop-up İçeriği') }}</label>
                        <x-tinymce.editor :livewire="false"
                                          height="600"
                                          name="settings[popupContent_{{ $locale->code }}]"
                                          value="{{ settings()->{'popupContent_'.$locale->code} }}"/>
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
