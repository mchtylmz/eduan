<div data-server-table-page="{{ $table }}" data-table-url-state="false" @include('components.admin.table-messages', ['deleteLabel' => __('Kaldır'), 'confirm' => __('Öğrencinin/Kullanıcının öğretmen ataması/eşleşmesi kaldırılacaktır, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <input type="hidden" name="user_id" data-table-filter value="{{ $contextUserId }}">
        <x-admin.filter-select :id="$table.'-status'" name="status" :label="__('Durum')" :options="['active' => __('Aktif'), 'passive' => __('Pasif')]" />
    </x-admin.filter-bar>
    <x-admin.server-table :id="$table.'-table'" :url="route($endpoint)" sort-name="username" :page-size="15" min-width="850px">
        <thead><tr>
            <th data-field="username" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
            <th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
</div>
