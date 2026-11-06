<form action="{{ route('admin.settings.store') }}" method="post" class="ajaxForm">
    @csrf
    <div class="row g-3 mb-4">
        <div class="col-md-6"><label class="form-label" for="sourceStorage">{{ __('sources.storage') }}</label>
            <select class="form-control" id="sourceStorage" name="settings[sourceStorage]">
                <option value="local" @selected(!\App\Helpers\Sources\SourceSettings::cloud())>{{ __('sources.local') }}</option>
                <option value="r2" @selected(\App\Helpers\Sources\SourceSettings::cloud())>{{ __('sources.cloud') }}</option>
            </select></div>
        <div class="col-md-6"><label class="form-label" for="sourceMaxMb">{{ __('sources.max_size') }}</label><input class="form-control" id="sourceMaxMb" type="number" min="1" max="100" name="settings[sourceMaxMb]" value="{{ settings()->sourceMaxMb ?: 100 }}"></div>
        <div class="col-12"><label class="form-label" for="sourceTypes">{{ __('sources.allowed_types') }}</label><input class="form-control" id="sourceTypes" name="settings[sourceTypes]" value="{{ implode(',', \App\Helpers\Sources\SourceSettings::types()) }}"><p class="form-text">pdf,html,jpg,jpeg,png,webp</p></div>
        @foreach(['sourceR2Account'=>'account','sourceR2Bucket'=>'bucket','sourceR2Key'=>'access_key','sourceR2PublicUrl'=>'public_url'] as $key=>$label)
            <div class="col-md-6"><label class="form-label" for="{{ $key }}">{{ __('sources.'.$label) }}</label><input class="form-control" id="{{ $key }}" name="settings[{{ $key }}]" value="{{ settings()->{$key} ?? '' }}" autocomplete="off"></div>
        @endforeach
        <div class="col-md-6"><label class="form-label" for="sourceR2Secret">{{ __('sources.secret') }}</label><input class="form-control" id="sourceR2Secret" type="password" name="settings[sourceR2Secret]" value="" autocomplete="new-password"><p class="form-text">{{ __('turnstile.secret_help') }}</p></div>
    </div>
    <p class="form-text">{{ __('sources.cloud_help') }}</p>
    @can('settings:update')<button type="submit" class="btn btn-alt-primary"><i class="fa fa-save me-1"></i>{{ __('Kaydet') }}</button>@endcan
</form>
