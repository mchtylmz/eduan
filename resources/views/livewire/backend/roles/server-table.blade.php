<div data-server-table-page="roles" @include('components.admin.table-messages', ['confirm' => __('Yetki silinecektir, işleme devam edilsin mi?')])>
    <x-admin.server-table id="roles-table" :url="route('admin.roles.getData')" sort-name="name" min-width="650px">
        <thead><tr>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yetki Adı') }}</th>
            <th data-field="group" data-formatter="eduanTableTextFormatter">{{ __('Yetki Grubu') }}</th>
            <th data-field="users_count" data-sortable="true" data-align="center">{{ __('Kullanıcı Sayısı') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="roles-detail" :title="__('management.role_detail')" :details="$details" icon="fa-shield-halved"
        :record-name="$details[__('Yetki Adı')] ?? null"
        :metrics="$details ? [__('Kullanıcı Sayısı') => $details[__('Kullanıcı Sayısı')]] : []"
        :edit-url="$detailId && auth()->user()->can('roles:update') ? route('admin.roles.edit', $detailId) : null" />
</div>
