<div data-server-table-page="pages" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
    <x-admin.server-table id="pages-table" :url="route('admin.pages.getData')" sort-name="id" sort-order="asc" min-width="800px">
        <thead><tr>
            <th data-field="title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Başlık') }}</th>
            <th data-field="sort" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Sıra') }}</th>
            <th data-field="link" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yönlendirme') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="pages-detail" :title="__('tables.pages_detail')" :details="$details" :edit-url="$editUrl" />
</div>
