<!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <div class="row">
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailHost">{{ __('Mail Host') }}</label>
            <input type="text" class="form-control" id="mailHost" name="settings[mailHost]" placeholder="{{ __('Mail Host') }}.." value="{{ settings()->mailHost }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailPort">{{ __('Mail Port') }} (587, 465, 25..)</label>
            <input type="number" class="form-control" id="mailPort" name="settings[mailPort]" placeholder="{{ __('Mail Port') }}.." value="{{ settings()->mailPort }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailUsername">{{ __('Gönderen Mail Kullanıcı Adı') }}</label>
            <input type="text" class="form-control" id="mailUsername" name="settings[mailUsername]" placeholder="{{ __('Mail Kullanıcı Adı') }}.." value="{{ settings()->mailUsername }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailPassword">{{ __('Gönderen Mail Parola') }}</label>
            <input type="text" class="form-control" id="mailPassword" name="settings[mailPassword]" placeholder="{{ __('Mail Parola') }}.." value="{{ settings()->mailPassword }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailFromName">{{ __('Gönderen Başlığı') }}</label>
            <input type="text" class="form-control" id="mailFromName" name="settings[mailFromName]" placeholder="{{ __('Gönderen Başlığı') }}.." value="{{ settings()->mailFromName }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailFromEmail">{{ __('Gönderen E-posta Adresi') }}</label>
            <input type="email" class="form-control" id="mailFromEmail" name="settings[mailFromEmail]" placeholder="{{ __('E-posta Adresi') }}.." value="{{ settings()->mailFromEmail }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="mailEncryptionType">{{ __('Mail Gönderim Türü') }}</label>
            <select id="mailEncryptionType" class="form-control" name="settings[mailEncryptionType]" required>
                <option value="SSL" @selected('SSL' == strtoupper(settings()->mailEncryptionType))>SSL</option>
                <option value="TLS" @selected('TLS' == strtoupper(settings()->mailEncryptionType))>TLS</option>
                <option value="NONE" @selected('NONE' == strtoupper(settings()->mailEncryptionType))>NONE</option>
            </select>
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
