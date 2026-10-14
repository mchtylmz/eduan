<form action="{{ route('admin.settings.store') }}" method="POST" class="js-validation">
    @csrf
    <p class="alert alert-info">{{ __('notifications.settings_help') }}</p>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><label class="form-label" for="notificationsEnabled">{{ __('notifications.feature') }}</label><select class="form-control" id="notificationsEnabled" name="settings[notificationsEnabled]"><option value="0" @selected(! \App\Helpers\WebPushSettings::enabled())>{{ __('study.off') }}</option><option value="1" @selected(\App\Helpers\WebPushSettings::enabled())>{{ __('study.on') }}</option></select></div>
        @foreach(['oneSignalAppId'=>'app_id', 'oneSignalOrigin'=>'origin', 'oneSignalSafariId'=>'safari_id', 'oneSignalLocalAppId'=>'local_app_id'] as $key=>$label)
            <div class="col-md-6"><label for="{{ $key }}" class="form-label">{{ __('notifications.'.$label) }}</label><input id="{{ $key }}" name="settings[{{ $key }}]" class="form-control" value="{{ settings()->$key }}" maxlength="255"></div>
        @endforeach
        @foreach(['oneSignalApiKey'=>'api_key', 'oneSignalLocalApiKey'=>'local_api_key'] as $key=>$label)
            <div class="col-md-6"><label for="{{ $key }}" class="form-label">{{ __('notifications.'.$label) }}</label><input id="{{ $key }}" name="settings[{{ $key }}]" type="password" class="form-control" value="" autocomplete="new-password" maxlength="2000"><p class="small text-muted">{{ __('notifications.key_help') }}</p></div>
        @endforeach
    </div>
    @php($explanation = \App\Models\Page::where('menu', \App\Enums\PageMenuEnum::NOTIFICATIONS)->first())
    @if($explanation && auth()->user()->can('pages:update'))<a class="btn btn-alt-secondary mb-3" href="{{ route('admin.pages.edit', $explanation) }}">{{ __('notifications.edit_explanation') }}</a>@endif
    @can('settings:update')<div class="mb-3"><button type="submit" class="btn btn-alt-primary">{{ __('study.save') }}</button></div>@endcan
</form>
