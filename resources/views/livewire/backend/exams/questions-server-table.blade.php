<div data-server-table-page="exam-questions" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
<x-admin.filter-bar><x-admin.filter-select id="exam-question-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" /><input type="hidden" name="exam_id" data-table-filter value="{{ $record->id }}"></x-admin.filter-bar>
<x-admin.server-table id="exam-questions-table" :url="route('admin.exams.getQuestionsData')" sort-name="status" :page-size="5" min-width="760px">
<thead><tr>
<th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
<th data-field="lesson_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ders') }}</th>
<th data-field="topic_title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Konu') }}</th>
<th data-field="attachment" data-sortable="true" data-formatter="eduanTableImageFormatter">{{ __('Soru') }}</th>
<th data-field="time" data-sortable="true" data-formatter="eduanTableSecondsFormatter">{{ __('Süre') }}</th>
<th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
</tr></thead>
</x-admin.server-table>
</div>
