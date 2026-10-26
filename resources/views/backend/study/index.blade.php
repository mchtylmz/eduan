@extends('backend.layouts.app')
@push('script')<script src="{{ asset('backend/assets/study-panel.js') }}?v={{ config('app.version') }}"></script>@endpush
@section('content')
    <div class="block block-rounded">
        <div class="block-header block-header-default"><h1 class="block-title">{{ $title }}</h1></div>
        <div class="block-content pb-4">
            <p class="text-muted">{{ __('study.panel_help') }}</p>
            @unless(\App\Helpers\StudyTrackingSettings::enabled() && \App\Helpers\StudyTrackingSettings::startedOn())
                <div class="alert alert-info">{{ __('study.disabled') }}</div>
            @endunless
            <div data-server-table-page="study" @include('components.admin.table-messages', ['confirm' => ''])>
                <x-admin.filter-bar>
                    <x-admin.filter-date id="study-week" name="week" :label="__('study.week')" :value="$week" />
                    <x-admin.filter-select id="study-state" name="state" :label="__('study.state')" :options="collect(['no_target', 'no_activity', 'zero_activity', 'low', 'on_track'])->mapWithKeys(fn($key) => [$key => __('study.state_'.$key)])->all()" />
                </x-admin.filter-bar>
                <x-admin.server-table id="study-table" :url="route('admin.study.data')" sort-name="name" min-width="920px">
                    <thead><tr>
                        <th data-field="name" data-sortable="true" data-formatter="eduanStudyStudentFormatter">{{ __('study.student') }}</th>
                        <th data-field="goal" data-formatter="eduanTableTextFormatter">{{ __('study.goal') }} ({{ __('study.questions') }} / {{ __('study.pages') }} / {{ __('study.hours') }})</th>
                        <th data-field="questions" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.questions') }}</th>
                        <th data-field="pages" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.pages') }}</th>
                        <th data-field="minutes" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.hours') }}</th>
                        <th data-field="achievement" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.overall') }}</th>
                        <th data-field="state" data-formatter="eduanTableTextFormatter">{{ __('study.state') }}</th>
                        <th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('study.actions') }}</th>
                    </tr></thead>
                </x-admin.server-table>
            </div>
        </div>
    </div>
@endsection
