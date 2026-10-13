<div data-server-table-page="lessons" @include('components.admin.table-messages', ['confirm' => __('Ders silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="lesson-locale" name="locale" :label="__('Dil')" :options="$languages" />
        <x-admin.filter-select id="lesson-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
    </x-admin.filter-bar>
    <x-admin.server-table id="lessons-table" :url="route('admin.lessons.getData')">
        <thead><tr>
            <th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ders Adı') }}</th>
            <th data-field="topics_count" data-sortable="true" data-align="center">{{ __('Konu Sayısı') }}</th>
            <th data-field="questions_count" data-sortable="true" data-align="center">{{ __('Soru Sayısı') }}</th>
            <th data-field="sort" data-sortable="true" data-align="center">{{ __('Sıra') }}</th>
            <th data-field="hits" data-sortable="true" data-align="center">{{ __('Görüntüleme') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="lessons-detail" :title="__('tables.lesson_detail')" :details="$details"
        icon="fa-book" :record-name="$details[__('Ders Adı')] ?? null" :record-code="$details[__('Kodu')] ?? null"
        :metrics="$details ? [__('Konu Sayısı') => $details[__('Konu Sayısı')], __('Soru Sayısı') => $details[__('Soru Sayısı')]] : []"
        :edit-url="$detailId && auth()->user()->can('lessons:update') ? route('admin.lessons.edit', $detailId) : null" />
</div>
