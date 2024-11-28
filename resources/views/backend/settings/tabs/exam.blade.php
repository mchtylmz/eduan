<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">


    <div class="row">
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="examAnswerCount">{{ __('Soru Cevap Sayısı') }}</label>
            <input type="number" min="1" class="form-control" id="examAnswerCount" name="settings[examAnswerCount]" placeholder="{{ __('Cevap Sayısı') }}.." value="{{ settings()->examAnswerCount ?? 4 }}" required>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="examlanguageCode">{{ __('Soru Varsayılan Dil') }}</label>
            <select id="examlanguageCode" class="form-control" name="settings[examlanguageCode]" required>
                @foreach($languages = data()->languages(active: true) as $locale)
                    <option value="{{ $locale->code }}" @selected($locale->code == settings()->examlanguageCode)>{{ str($locale->code)->upper() }} - {{ $locale->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="examTime">{{ __('Soru Süresi') }}</label>
            <input type="number" min="1" class="form-control" id="examTime" name="settings[examTime]" placeholder="{{ __('Süre') }}.." value="{{ settings()->examTime ?? 300 }}" required>
        </div>
    </div>

    <hr>

    <div class="block block-rounded">
        <ul class="nav nav-tabs nav-tabs-alt" role="tablist">
            @foreach($languages = data()->languages(active: true) as $locale)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link {{ $loop->index == 0 ? 'active': '' }}"
                            id="exam-{{ $locale->code }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#exam-{{ $locale->code }}"
                            role="tab"
                            aria-controls="exam-{{ $locale->code }}"
                            aria-selected="{{ $loop->index ? 'true': 'false' }}">
                        {{ str($locale->code)->upper() }} - {{ $locale->name }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="block-content tab-content">
            @foreach($languages = data()->languages(active: true) as $locale)
                <div class="tab-pane row py-1 {{ $loop->index == 0 ? 'active show': '' }}"
                     id="exam-{{ $locale->code }}"
                     role="tabpanel"
                     aria-labelledby="exam-{{ $locale->code }}"
                     tabindex="0">

                    <div class="mb-2">
                        <label class="form-label">
                            {{ __('Üye girişi yapılmadan Test Çözememe Durumu Uyarısı') }}
                        </label>
                        <textarea rows="5"
                                  class="form-control"
                                  id="examSolveNotAuthDescription_{{ $locale->code }}"
                                  name="settings[examSolveNotAuthDescription_{{ $locale->code }}]"
                                  placeholder="{{ __('Açıklama') }}.."
                        >{{ settings()->{'examSolveNotAuthDescription_'.$locale->code} }}</textarea>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <label class="form-label">
                            {{ __('Premium üyelik yetkisi verilmeden Test Çözememe Durumu Uyarısı') }}
                        </label>
                        <textarea rows="5"
                                  class="form-control"
                                  id="examSolveNotPremiumDescription_{{ $locale->code }}"
                                  name="settings[examSolveNotPremiumDescription_{{ $locale->code }}]"
                                  placeholder="{{ __('Açıklama') }}.."
                        >{{ settings()->{'examSolveNotPremiumDescription_'.$locale->code} }}</textarea>
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
