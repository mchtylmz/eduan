<div data-server-table-page="faqs" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="faqs-locale" name="locale" :label="__('Dil')" :options="$languages" />
        <x-admin.filter-select id="faqs-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
    </x-admin.filter-bar>
    <x-admin.server-table id="faqs-table" :url="route('admin.pages.faqs.getData')" sort-name="id" sort-order="asc" min-width="800px">
        <thead><tr>
            <th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
            <th data-field="title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Başlık') }}</th>
            <th data-field="sort" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Sıra') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="faqs-detail" :title="__('tables.faqs_detail')" :details="$details" :edit-url="$editUrl" />
</div>
