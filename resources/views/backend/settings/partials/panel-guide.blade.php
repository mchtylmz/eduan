@if(\App\Helpers\PanelGuide::visibleTo(auth()->user()))
    <div class="mb-3 mt-4">
        <label class="form-label" for="panelGuideUrl">{{ __('guide.url_label') }}</label>
        <input type="url" class="form-control" id="panelGuideUrl" name="settings[panelGuideUrl]"
               placeholder="https://example.com/documents.html" maxlength="2048"
               value="{{ old('settings.panelGuideUrl', settings()->panelGuideUrl ?? '') }}"
               aria-describedby="panelGuideUrl-help">
        <div class="form-text" id="panelGuideUrl-help">{{ __('guide.url_help') }}</div>
        <x-badge.error field="settings.panelGuideUrl" />
    </div>
@endif
