<div data-server-table-page="blogs" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="blogs-locale" name="locale" :label="__('Dil')" :options="$languages" />
        <x-admin.filter-select id="blogs-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
    </x-admin.filter-bar>
    <x-admin.server-table id="blogs-table" :url="route('admin.blogs.getData')" sort-name="published_at" sort-order="desc" min-width="800px">
        <thead><tr>
            <th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
            <th data-field="title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Başlık') }}</th>
            <th data-field="hits" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Görüntüleme') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="published_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yayın Tarihi') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="blogs-detail" :title="__('tables.blogs_detail')" :details="$details" :edit-url="$editUrl" />
</div>
