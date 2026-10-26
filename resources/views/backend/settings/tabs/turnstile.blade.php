<form action="{{ route('admin.settings.store') }}" method="POST" class="js-validation">
    @csrf
    <p class="alert alert-info">{{ __('turnstile.settings_help') }}</p>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="turnstileEnabled">{{ __('turnstile.feature') }}</label>
            <select class="form-control" id="turnstileEnabled" name="settings[turnstileEnabled]">
                <option value="0" @selected(! \App\Helpers\TurnstileSettings::enabled())>{{ __('study.off') }}</option>
                <option value="1" @selected(\App\Helpers\TurnstileSettings::enabled())>{{ __('study.on') }}</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="turnstileSiteKey">{{ __('turnstile.site_key') }}</label>
            <input class="form-control" id="turnstileSiteKey" name="settings[turnstileSiteKey]" value="{{ \App\Helpers\TurnstileSettings::siteKey() }}" placeholder="{{ __('turnstile.site_key') }}" maxlength="200" autocomplete="off">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="turnstileSecretKey">{{ __('turnstile.secret_key') }}</label>
            <input class="form-control" type="password" id="turnstileSecretKey" name="settings[turnstileSecretKey]" value="" placeholder="{{ __('turnstile.secret_key') }}" maxlength="200" autocomplete="new-password">
            <p class="small text-muted mt-2">{{ __('turnstile.secret_help') }}</p>
        </div>
    </div>
    <p class="small text-muted">{{ __('turnstile.host_help', ['host' => parse_url(config('app.url'), PHP_URL_HOST)]) }}</p>
    @can('settings:update')
        <div class="mb-3"><button class="btn btn-alt-primary" type="submit"><i class="fa fa-check me-1" aria-hidden="true"></i>{{ __('study.save') }}</button></div>
    @endcan
</form>
