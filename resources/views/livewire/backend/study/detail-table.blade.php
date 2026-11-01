<div data-server-table-page="study-{{ $type }}" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
    @if($type !== 'history')<form data-table-filters class="d-none"><input type="hidden" name="lang" value="{{ app()->getLocale() }}" data-table-filter><input type="hidden" name="table" value="{{ $type }}" data-table-filter><input type="hidden" name="week" value="{{ $weekStart }}" data-table-filter></form>@endif
    @if($type === 'history')
        <x-admin.filter-bar>
            <input type="hidden" name="lang" value="{{ app()->getLocale() }}" data-table-filter>
            <input type="hidden" name="table" value="history" data-table-filter><input type="hidden" name="week" value="{{ $weekStart }}" data-table-filter>
            <div class="col-12 col-md-8">
                <label for="study-panel-season" class="form-label">{{ __('study.period_with_dates') }}</label>
                <select id="study-panel-season" name="season" class="form-select" data-table-filter>
                    <option value="" @selected((string) $seasonId === '')>{{ __('Tümü') }}</option>
                    @foreach($seasons as $season)<option value="{{ $season->id }}" @selected((string) $seasonId === (string) $season->id)>{{ $season->name }} · {{ \App\Helpers\StudyCalendar::label($season->start_date) }} — {{ \App\Helpers\StudyCalendar::label($season->end_date) }}</option>@endforeach
                </select>
            </div>
        </x-admin.filter-bar>
    @endif
    <div class="d-flex justify-content-end mb-3"><a class="btn btn-success" data-table-export href="{{ route('admin.study.detail-export', ['user' => $studentId]) }}" data-study-detail-export="{{ $type }}"><i class="fa fa-file-excel me-1"></i>{{ __('study.export_excel') }}</a></div>
    <x-admin.server-table :id="'study-'.$type.'-table'" :url="route('admin.study.detail-data', ['user' => $studentId, 'week' => $weekStart, 'table' => $type])" :sort-name="$type === 'history' ? 'week' : ($type === 'notes' ? 'created_at' : ($type === 'topics' ? 'name' : 'date'))" :sort-order="$type === 'topics' ? 'asc' : 'desc'" min-width="760px">
        <thead><tr>
@if($type === 'history')
<th data-field="week" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.week') }}</th>
<th data-field="questions" data-formatter="eduanTableTextFormatter">{{ __('study.questions') }}<div class="small fw-normal">{{ __('study.goal_actual') }}</div></th>
<th data-field="pages" data-formatter="eduanTableTextFormatter">{{ __('study.pages') }}<div class="small fw-normal">{{ __('study.goal_actual') }}</div></th>
<th data-field="minutes" data-formatter="eduanTableTextFormatter">{{ __('study.hours') }}<div class="small fw-normal">{{ __('study.goal_actual') }}</div></th>
<th data-field="achievement" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.overall') }}</th>
<th data-field="state" data-formatter="eduanTableTextFormatter">{{ __('study.state') }}</th>
<th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('study.actions') }}</th>
@endif
@if($type === 'days')
<th data-field="date" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.date') }}</th>
<th data-field="manual_questions" data-formatter="eduanTableTextFormatter">{{ __('study.manual_questions') }}</th>
<th data-field="automatic_tests" data-formatter="eduanTableTextFormatter">{{ __('study.automatic_tests') }}</th>
<th data-field="automatic_exams" data-formatter="eduanTableTextFormatter">{{ __('study.automatic_exams') }}</th>
<th data-field="questions" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.questions') }}</th>
<th data-field="pages" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.pages') }}</th>
<th data-field="minutes" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.hours') }}</th>
<th data-field="fragments" data-formatter="eduanTableTextFormatter">{{ __('study.fragments') }}</th>
@endif
@if($type === 'entries')
<th data-field="date" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.date') }}</th>
<th data-field="questions" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.questions') }}</th>
<th data-field="pages" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.pages') }}</th>
<th data-field="minutes" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.hours') }}</th>
<th data-field="actions" data-formatter="eduanStudyEditFormatter">{{ __('study.actions') }}</th>
@endif
@if($type === 'notes')
<th data-field="author" data-formatter="eduanTableTextFormatter">{{ __('study.note_author_panel') }}</th>
<th data-field="body" data-formatter="eduanStudyNoteFormatter">{{ __('study.note_body_panel') }}</th>
<th data-field="visibility" data-formatter="eduanTableTextFormatter">{{ __('study.note_visibility_panel') }}</th>
<th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.date') }}</th>
<th data-field="actions" data-formatter="eduanStudyEditFormatter">{{ __('study.actions') }}</th>
@endif
@if($type === 'topics')
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.topic') }}</th>
<th data-field="lesson" data-formatter="eduanTableTextFormatter">{{ __('study.lesson_panel') }}</th>
<th data-field="correct" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.correct') }}</th>
<th data-field="incorrect" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.incorrect') }}</th>
<th data-field="rate" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('study.accuracy') }}</th>
<th data-field="state" data-formatter="eduanTableTextFormatter">{{ __('study.state') }}</th>
@endif
        </tr></thead>
    </x-admin.server-table>
</div>
