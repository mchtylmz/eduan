<div data-server-table-page="newsletter" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
    <x-admin.server-table id="newsletter-table" :url="route('admin.newsletter.getData')" sort-name="id" sort-order="desc" min-width="800px">
        <thead><tr>
            <th data-field="id" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('ID') }}</th>
            <th data-field="email" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Email') }}</th>
            <th data-field="ip" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('IP') }}</th>
            <th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kayıt Tarihi') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="newsletter-detail" :title="__('tables.newsletter_detail')" :details="$details" :edit-url="$editUrl" />
</div>
