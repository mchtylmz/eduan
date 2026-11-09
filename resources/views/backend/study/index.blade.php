@extends('backend.layouts.app')
@push('script')<script src="{{ asset('backend/assets/study-panel.js') }}?v={{ config('app.version') }}"></script>@endpush
@push('style')<link rel="stylesheet" href="{{ asset('backend/assets/study-panel.css') }}?v={{ config('app.version') }}">@endpush
@section('content')
    <div class="block block-rounded" data-server-table-page="study" @include('components.admin.table-messages', ['confirm' => ''])>
        <div class="block-header block-header-default flex-wrap gap-3">
            <h1 class="block-title">{{ $title }}</h1>
            <div class="block-options d-flex flex-wrap justify-content-end gap-2 ms-auto mw-100">
                <a class="btn btn-success text-wrap" data-table-export href="{{ route('admin.study.export') }}"><i class="fa fa-file-excel me-1"></i>{{ __('study.export_excel') }}</a>
                @if(auth()->user()->can(['study:communicate', 'communications:view', 'communications:send']))
                    <a class="btn btn-alt-primary text-wrap" data-table-query-link href="{{ route('admin.study.compose', ['channel' => 'email']) }}"><i class="fa fa-envelope me-1"></i>{{ __('study.email_filtered') }}</a>
                    <a class="btn btn-alt-primary text-wrap" data-table-query-link href="{{ route('admin.study.compose', ['channel' => 'push']) }}"><i class="fa fa-bell me-1"></i>{{ __('study.push_filtered') }}</a>
                @endif
            </div>
        </div>
        <div class="block-content pb-4">
            @unless(\App\Helpers\StudyTrackingSettings::enabled() && \App\Helpers\StudyTrackingSettings::startedOn())
                <div class="alert alert-info">{{ __('study.disabled') }}</div>
            @endunless
            <div>
                <x-admin.filter-bar :class="'study-list-filters'.(auth()->user()->can('user-type:admin') ? ' has-teacher' : '')">
                    <input type="hidden" data-table-filter name="lang" value="{{ app()->getLocale() }}">
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="study-week">{{ __('study.choose_week') }}</label>
                        <input type="text" id="study-week" name="week" value="{{ $week }}" class="form-control" data-table-filter data-study-week data-locale="{{ app()->getLocale() }}" aria-describedby="study-week-help" autocomplete="off">
                        <div id="study-week-help" class="visually-hidden">{{ __('study.choose_week_help') }}</div>
                    </div>
                    @can('user-type:admin')
                        <x-admin.filter-select id="study-teacher" name="teacher_id" :searchable="true" :label="__('study.teacher')" :options="$teachers" />
                    @endcan
                    <x-admin.filter-select id="study-state" name="state" :searchable="true" :label="__('study.state')" :options="\App\Helpers\StudyPanelQuery::states()" />
                    <x-admin.filter-select id="study-status" name="status" :label="__('study.student_status')" :options="['active' => __('study.student_active'), 'passive' => __('study.student_passive')]" />
                </x-admin.filter-bar>
                <x-admin.server-table id="study-table" :url="route('admin.study.data')" sort-name="name" min-width="1000px">
                    <thead><tr>
                        <th data-field="name" data-sortable="true" data-formatter="eduanStudyStudentFormatter">{{ __('study.student') }}</th>
                        <th data-field="goal" data-formatter="eduanStudyGoalFormatter">{{ __('study.goal') }}</th>
                        <th data-field="questions" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.questions') }}</th>
                        <th data-field="pages" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.pages') }}</th>
                        <th data-field="minutes" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.hours') }}</th>
                        <th data-field="achievement" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.overall') }}</th>
                        <th data-field="state" data-formatter="eduanStudyStateFormatter">{{ __('study.state') }}</th>
                        <th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('study.actions') }}</th>
                    </tr></thead>
                </x-admin.server-table>
            </div>
        </div>
    </div>
@endsection
