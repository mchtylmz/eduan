<div data-server-table-page="topics" @include('components.admin.table-messages', ['confirm' => __('Konu silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="topic-lessons" name="lessons" :label="__('Dersler')" :options="$lessons" multiple />
        <x-admin.filter-select id="topic-locale" name="locale" :label="__('Dil')" :options="$languages" :value="settings()->examlanguageCode ?? app()->getLocale()" />
        <x-admin.filter-select id="topic-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
    </x-admin.filter-bar>
    <x-admin.server-table id="topics-table" :url="route('admin.topics.getData')" min-width="1000px">
        <thead><tr>
            <th data-field="lesson_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ders') }}</th>
            <th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
            <th data-field="title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Konu') }}</th>
            <th data-field="questions_count" data-sortable="true" data-align="center">{{ __('Soru Sayısı') }}</th>
            <th data-field="sort" data-sortable="true" data-align="center">{{ __('Sıra') }}</th>
            <th data-field="hits" data-sortable="true" data-align="center">{{ __('Görüntüleme') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="topics-detail" :title="__('tables.topic_detail')" :details="$details"
        icon="fa-layer-group" :record-name="$details[__('Konu')] ?? null" :record-code="$details[__('Ders')] ?? null"
        :metrics="$details ? [__('Soru Sayısı') => $details[__('Soru Sayısı')]] : []"
        :edit-url="$detailId && auth()->user()->can('topics:update') ? route('admin.topics.edit', $detailId) : null" />
</div>
