<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <div class="row">
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="primaryColor">{{ __('Ana Renk') }}</label>
            <input type="color" class="form-control" id="primaryColor" name="settings[primaryColor]" value="{{ settings()->primaryColor ?? '#000' }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="secondaryColor">{{ __('İkinci Renk') }}</label>
            <input type="color" class="form-control" id="secondaryColor" name="settings[secondaryColor]" value="{{ settings()->secondaryColor ?? '#000' }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="thirdColor">{{ __('Üçüncü Renk') }}</label>
            <input type="color" class="form-control" id="thirdColor" name="settings[thirdColor]" value="{{ settings()->thirdColor ?? '#000' }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="fourthColor">{{ __('Dördüncü Renk') }}</label>
            <input type="color" class="form-control" id="fourthColor" name="settings[fourthColor]" value="{{ settings()->fourthColor ?? '#000' }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="fifthColor">{{ __('Beşinci Renk') }}</label>
            <input type="color" class="form-control" id="fifthColor" name="settings[fifthColor]" value="{{ settings()->fifthColor ?? '#000' }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="chartColor">{{ __('İstatistik - Grafik Rengi') }}</label>
            <input type="color" class="form-control" id="chartColor" name="settings[chartColor]" value="{{ settings()->chartColor ?? '#000' }}" required>
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
