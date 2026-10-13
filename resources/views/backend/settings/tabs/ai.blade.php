<!-- form -->

@push('script')
    @php
        $modelEfforts = [];
        foreach (\App\Helpers\AiSolutionRequest::MODELS as $key) {
            $modelEfforts[$key] = \App\Helpers\AiSolutionRequest::efforts($key);
        }
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const model = document.getElementById('gptModel');
            const effort = document.getElementById('gptEffect');
            if (!model || !effort) return;
            const allowed = @json($modelEfforts);
            function updateEffort() {
                // This Bootstrap Select version appends its internal option data on refresh.
                // Recreate only this picker so model changes cannot duplicate its options.
                const picker = window.jQuery?.fn.selectpicker ? window.jQuery(effort) : null;
                if (picker?.data('selectpicker')) picker.selectpicker('destroy');
                const choices = allowed[model.value] || ['low'];
                for (const option of effort.options) option.disabled = !choices.includes(option.value);
                if (!choices.includes(effort.value)) effort.value = 'low';
                if (picker) picker.selectpicker();
            }
            model.addEventListener('change', updateEffort);
            updateEffort();
        });
    </script>
@endpush
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <div class="mb-3">
        <label class="form-label" for="gptApiKey">{{ __('ChatGPT API KEY') }}</label>
        <textarea rows="3"
                  class="form-control"
                  id="gptApiKey"
                  name="settings[gptApiKey]"
                  placeholder="{{ __('API KEY') }}.."
                  required>{{ settings()->gptApiKey }}</textarea>
    </div>
    <hr>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="gptModel">{{ __('Yapa Zeka Modeli') }}</label>
            <select id="gptModel" class="form-control selectpicker" data-live-search="true" data-size="10" name="settings[gptModel]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(aiHelper()->gptLists() as $modelKey => $modelName)
                    <option value="{{ $modelKey }}" @selected($modelKey == settings()->gptModel)>{{ $modelName }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="gptLimitCount">{{ __('Kullanıcı') }} / {{ __('Günlük Yapay Zeka Kullanım Limiti') }} </label>
            <input type="number" min="0" class="form-control" id="gptLimitCount" name="settings[gptLimitCount]" placeholder="{{ __('Kullanım sayısı') }}.." value="{{ settings()->gptLimitCount ?? 5 }}" required>
        </div>
        <div class="col-lg-6 mb-3">
            <label class="form-label" for="gptEffect">{{ __('Yapa Zeka Reasoning effort') }}</label>
            <select id="gptEffect" class="form-control selectpicker" data-live-search="true" data-size="10" name="settings[gptEffect]">
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(aiHelper()->gptEffects() as $key => $name)
                    <option value="{{ $key }}" @selected($key == settings()->gptEffect)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-12 mb-3">
            <label class="form-label" for="gptDeveloperMessage">{{ __('Yapay Zeka Geliştirici/Sistem Mesajı') }}</label>
            <textarea rows="8"
                      class="form-control"
                      id="gptDeveloperMessage"
                      name="settings[gptDeveloperMessage]"
                      placeholder="{{ __('Geliştirici/Sistem Mesajı') }}.."
            >{{ settings()->gptDeveloperMessage }}</textarea>
        </div>
        <div class="col-lg-12 mb-3">
            <label class="form-label" for="gptUserMessage">{{ __('Yapay Zeka Kullanıcı Mesajı') }}</label>
            <textarea rows="8"
                      class="form-control"
                      id="gptUserMessage"
                      name="settings[gptUserMessage]"
                      placeholder="{{ __('Kullanıcı Mesajı') }}.."
            >{{ settings()->gptUserMessage }}</textarea>
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
