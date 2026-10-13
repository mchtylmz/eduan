<div data-server-table-page="logs" @include('components.admin.table-messages', ['confirm' => ''])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="log-users" name="users" :label="__('Kullanıcılar')" :options="$users" multiple :dropdown-size="10" />
        <x-admin.filter-select id="log-type" name="log_type" :label="__('İşlem')" :options="$types" />
    </x-admin.filter-bar>
    <x-admin.server-table id="logs-table" :url="route('admin.settings.getLogsData')" sort-name="id" sort-order="desc" min-width="1100px">
        <thead><tr>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
            <th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
            <th data-field="log_type" data-sortable="true" data-formatter="eduanTableLogFormatter">{{ __('İşlem') }}</th>
            <th data-field="agent" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Agent') }}</th>
            <th data-field="browser" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Tarayıcı') }}</th>
            <th data-field="ip" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('IP') }}</th>
            <th data-field="log_date" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Tarih') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="logs-detail" :title="__('management.log_detail')" :details="[]" icon="fa-clock-rotate-left">
        @if($detailId)
            <livewire:settings.log-detail-table :log-id="$detailId" :key="'log-detail-'.$detailId" />
        @endif
    </x-admin.table-detail>
</div>
