<div data-server-table-page="languages" @include('components.admin.table-messages', ['confirm' => __('Dil ve çeviriler kalıcı olarak silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="languages-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
    </x-admin.filter-bar>
    <x-admin.server-table id="languages-table" :url="route('admin.languages.getData')" sort-name="id" sort-order="asc" min-width="800px">
        <thead><tr>
            <th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Adı') }}</th>
            <th data-field="is_default" data-formatter="eduanTableStatusFormatter">{{ __('Varsayılan') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="languages-detail" :title="__('tables.languages_detail')" :details="$details" :edit-url="$editUrl" />
</div>
