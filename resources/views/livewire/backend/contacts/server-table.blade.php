<div data-server-table-page="contacts" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="contacts-locale" name="locale" :label="__('Dil')" :options="$languages" />
        <x-admin.filter-select id="contacts-has-read" name="has_read" :label="__('Durum')" :options="[1 => __('Okundu'), 0 => __('Okunmamış')]" multiple />
    </x-admin.filter-bar>
    <x-admin.server-table id="contacts-table" :url="route('admin.contacts.getData')" sort-name="created_at" sort-order="desc" min-width="1100px">
        <thead><tr>
            <th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim Soyisim') }}</th>
            <th data-field="email" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('E-posta Adresi') }}</th>
            <th data-field="phone" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Telefon Numarası') }}</th>
            <th data-field="school_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Okul Adı') }}</th>
            <th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Mesaj Tarihi') }}</th>
            <th data-field="has_read" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="contacts-detail" :title="__('tables.contacts_detail')" :details="$details" :edit-url="$editUrl" />
</div>
