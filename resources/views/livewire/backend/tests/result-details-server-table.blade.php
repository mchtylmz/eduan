<div data-server-table-page="test-result-details" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
<div class="bg-body-light py-2 px-3 fw-semibold mb-3">{{ $record->user?->email ?? '-' }} — {{ $record->user?->display_name ?? '-' }}</div><x-admin.filter-bar><x-admin.filter-select id="detail-lessons" name="lessons" :label="__('Dersler')" :options="$lessons" multiple dropdown-size="10" /><x-admin.filter-select id="detail-topics" name="topics" :label="__('Konular')" :options="$topics" multiple dropdown-size="10" /><input type="hidden" name="tests_result_id" data-table-filter value="{{ $record->id }}"></x-admin.filter-bar>
<x-admin.server-table id="test-result-details-table" :url="route('admin.tests.getResultDetailsData')" sort-name="id" :page-size="5" min-width="760px">
<thead><tr>
<th data-field="attachment" data-sortable="true" data-formatter="eduanTableImageFormatter">{{ __('Soru') }}</th>
<th data-field="answer" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yanıt') }}</th>
<th data-field="correct" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Yanıt') }}</th>
<th data-field="lesson_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ders') }}</th>
<th data-field="topic_title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Konu') }}</th>
<th data-field="point" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Puan') }}</th>
<th data-field="updated_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Tarih') }}</th>
</tr></thead>
</x-admin.server-table>
</div>
