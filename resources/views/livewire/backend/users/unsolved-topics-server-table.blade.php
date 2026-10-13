<div data-server-table-page="user-unsolved-topics" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
    <x-admin.filter-bar>
        <input type="hidden" name="user_id" data-table-filter value="{{ $contextUserId }}">
        <x-admin.filter-select id="user-unsolved-status" name="status" :label="__('Durum')" :options="['active' => __('Aktif'), 'passive' => __('Pasif')]" />
    </x-admin.filter-bar>
    <x-admin.server-table id="user-unsolved-topics-table" :url="route('admin.users.getUnsolvedTopicsData')" sort-name="code" :page-size="15" min-width="650px">
        <thead><tr>
            <th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
            <th data-field="title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Konu') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
        </tr></thead>
    </x-admin.server-table>
</div>
