<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">
    <!-- row -->
    <div class="row">
        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="siteFavicon">{{ __('Site Favicon') }}</label>
                <input type="file" class="dropify" id="siteFavicon" name="images[siteFavicon]"
                       data-show-remove="false"
                       data-show-errors="true"
                       data-allowed-file-extensions="jpg png jpeg webp"
                       accept=".jpg,.png,.jpeg,.webp"
                       data-max-file-size="10M"
                       @if($siteFavicon = settings()->siteFavicon) data-default-file="{{ asset($siteFavicon) }}" @endif
                />
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="siteLogo">{{ __('Site Logo') }}</label>
                <input type="file" class="dropify" id="siteLogo" name="images[siteLogo]"
                       data-show-remove="false"
                       data-show-errors="true"
                       data-allowed-file-extensions="jpg png jpeg webp"
                       accept=".jpg,.png,.jpeg,.webp"
                       data-max-file-size="10M"
                       @if($siteLogo = settings()->siteLogo) data-default-file="{{ asset($siteLogo) }}" @endif
                />
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mb-3">
                <label class="form-label" for="siteLogoWhite">{{ __('Site Beyaz Logo') }}</label>
                <input type="file" class="dropify" id="siteLogoWhite" name="images[siteLogoWhite]"
                       data-show-remove="false"
                       data-show-errors="true"
                       data-allowed-file-extensions="jpg png jpeg webp"
                       accept=".jpg,.png,.jpeg,.webp"
                       data-max-file-size="10M"
                       @if($siteLogoWhite = settings()->siteLogoWhite) data-default-file="{{ asset($siteLogoWhite) }}" @endif
                />
            </div>
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
