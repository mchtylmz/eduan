<div data-server-table-page="user-favorites" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
    <x-admin.filter-bar>
        <input type="hidden" name="user_id" data-table-filter value="{{ $contextUserId }}">
        <x-admin.filter-select id="user-favorites-status" name="status" :label="__('Durum')" :options="['active' => __('Aktif'), 'passive' => __('Pasif')]" />
    </x-admin.filter-bar>
    <x-admin.server-table id="user-favorites-table" :url="route('admin.users.getFavoritesData')" sort-name="code" :page-size="5" min-width="800px">
        <thead><tr>
            <th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
            <th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Adı') }}</th>
            <th data-field="visibility" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Görünüm') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
        </tr></thead>
    </x-admin.server-table>
</div>
