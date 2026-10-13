<div data-server-table-page="exams" @include('components.admin.table-messages', ['confirm' => __('Test silinecektir, soruya bağlı öğeler silinmeyecektir, işleme devam edilsin mi?')])>
<x-admin.filter-bar>
<x-admin.filter-select id="exams-lessons" name="lessons" :label="__('Dersler')" :options="$lessons" multiple dropdown-size="10" />
<x-admin.filter-select id="exams-topics" name="topics" :label="__('Konular')" :options="$topics" multiple dropdown-size="10" />
<x-admin.filter-select id="exams-locale" name="locale" :label="__('Dil')" :options="$languages" :value="settings()->examlanguageCode ?? app()->getLocale()" />
<x-admin.filter-select id="exams-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" /><x-admin.filter-select id="exam-visibility" name="visibility" :label="__('Görünüm')" :options="\App\Enums\VisibilityEnum::options()" />
</x-admin.filter-bar>
<x-admin.server-table id="exams-table" :url="route('admin.exams.getData')" sort-name="id" sort-order="desc" min-width="1100px">
<thead><tr>
<th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
<th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Adı') }}</th>
<th data-field="questions_count" data-sortable="true" data-formatter="eduanTableQuestionsFormatter">{{ __('Sorular') }}</th>
<th data-field="visibility" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Görünüm') }}</th>
<th data-field="hits" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Görüntüleme') }}</th>
<th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
<th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
</tr></thead>
</x-admin.server-table>
<x-admin.table-detail id="exams-detail" :title="__('Testler')" :details="$details" icon="fa-pen" :record-name="$details[__('Adı')] ?? null" :record-code="$details[__('Kodu')] ?? null" :edit-url="$detailId && auth()->user()->can('exams:update') ? route('admin.exams.edit', $detailId) : null" />
</div>
