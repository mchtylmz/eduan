<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">


    <div class="row">
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="testlanguageCode">{{ __('Sınav Varsayılan Dil') }}</label>
            <select id="testlanguageCode" class="form-control" name="settings[testlanguageCode]" required>
                @foreach($languages = data()->languages(active: true) as $locale)
                    <option value="{{ $locale->code }}" @selected($locale->code == settings()->testlanguageCode)>{{ str($locale->code)->upper() }} - {{ $locale->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="testTime">{{ __('Sınav Süresi') }}</label>
            <input type="number" min="30" class="form-control" id="testTime" name="settings[testTime]" placeholder="{{ __('Süre') }}.." value="{{ settings()->testTime ?? 300 }}" required>
            <small>{{ __('Sınav süresi saniye olarak girilmelidir. 60 saniye = 1 dakika') }}</small>
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
