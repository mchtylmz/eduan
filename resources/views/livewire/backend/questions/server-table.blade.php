<div class="is-card-view" data-user-card-view wire:ignore.self data-server-table-page="questions" @include('components.admin.table-messages', ['confirm' => __('Soru havuzdan silinecektir, işleme devam edilsin mi?')])>
<x-admin.filter-bar>
<x-admin.filter-select id="questions-lessons" name="lessons" :label="__('Dersler')" :options="$lessons" multiple dropdown-size="10" />
<x-admin.filter-select id="questions-topics" name="topics" :label="__('Konular')" :options="$topics" multiple dropdown-size="10" />
<x-admin.filter-select id="questions-locale" name="locale" :label="__('Dil')" :options="$languages" :value="settings()->examlanguageCode ?? app()->getLocale()" />
<x-admin.filter-select id="questions-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
<x-admin.table-sort id="questions-sort" :options="['language'=>__('Dil'),'lesson_name'=>__('Ders'),'topic_title'=>__('Konu'),'attachment'=>__('Soru'),'code'=>__('Kodu'),'sort'=>__('Sıralama'),'time'=>__('Süre'),'status'=>__('Durum')]" sort="sort" />
</x-admin.filter-bar>
<x-admin.server-table id="questions-table" :url="route('admin.questions.getData')" sort-name="sort" sort-order="desc" min-width="1100px" :card-view="true">
<thead><tr>
<th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
<th data-field="lesson_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ders') }}</th>
<th data-field="topic_title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Konu') }}</th>
<th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
<th data-field="attachment" data-sortable="true" data-formatter="eduanTableImageFormatter">{{ __('Soru') }}</th>
<th data-field="sort" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Sıra') }}</th>
<th data-field="time" data-sortable="true" data-formatter="eduanTableSecondsFormatter">{{ __('Süre') }}</th>
<th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
<th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
</tr></thead>
</x-admin.server-table>
<x-admin.table-detail id="questions-detail" :title="__('Soru')" :details="$details" icon="fa-question" :record-name="$question?->code" :record-code="$question?->topic?->title" :edit-url="$detailId && auth()->user()->can('questions:update') ? route('admin.questions.edit', $detailId) : null">
    @if($question?->attachment)
        <a href="{{ asset($question->attachment_url) }}" target="_blank" rel="noopener"><img src="{{ asset($question->attachment_url) }}" class="img-fluid admin-question-detail-image" alt="{{ $question->code }}"></a>
    @endif
</x-admin.table-detail>
</div>
