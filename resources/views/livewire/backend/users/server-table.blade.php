<div class="is-card-view" data-server-table-page="users" data-user-card-view wire:ignore.self @include('components.admin.table-messages', ['confirm' => __('Kullanıcının giriş yapabilme özelliği kapatılacaktır, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        <x-admin.filter-select id="user-student-group" name="student_group" :label="__('dashboard.student_group')"
            :options="['normal' => __('dashboard.standard_students'), 'premium' => __('Premium Öğrenciler'), 'all' => __('dashboard.all_students')]" />
        <x-admin.filter-date id="user-registered-since" name="registered_since" :label="__('dashboard.exact_registered_from')" :time="true" />
        <x-admin.filter-select id="user-roles" name="roles" :label="__('Yetkiler')" :options="$roles" multiple />
        <x-admin.filter-select id="user-verified" name="email_verified" :label="__('E-posta Onay')" :options="[1 => __('Onaylı'), 0 => __('Onaylı Değil')]" multiple />
        <x-admin.filter-select id="user-status" name="status" :label="__('Durum')" :options="\App\Enums\StatusEnum::options()" />
        @foreach(['last_login' => __('Son Giriş'), 'registered' => __('Kayıt Tarihi')] as $key => $label)
            @foreach(['from' => __('tables.date_from'), 'to' => __('tables.date_to')] as $bound => $boundLabel)
                <x-admin.filter-date :id="$key.'_'.$bound" :name="$key.'_'.$bound" :label="$label.' — '.$boundLabel" />
            @endforeach
        @endforeach
    </x-admin.filter-bar>
    <x-admin.server-table id="users-table" :url="route('admin.users.getData')" sort-name="username" min-width="1060px" :card-view="true">
        <thead><tr>
            <th data-field="roles" data-formatter="eduanTableRolesFormatter">{{ __('Yetki') }}</th>
            <th data-field="username" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
            <th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
            <th data-field="email_verified" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('E-posta Onay') }}</th>
            <th data-field="status" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="last_login_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Son Giriş') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="users-detail" :title="__('tables.user_detail')" :details="$details"
        icon="fa-user" :record-name="$detailId ? trim(($details[__('İsim')] ?? '').' '.($details[__('Soyisim')] ?? '')) : null" :record-code="$details[__('Kullanıcı Adı')] ?? null"
        :metrics="$metrics"
        :edit-url="$detailId && auth()->user()->can('users:update') ? route('admin.users.edit', $detailId) : null" />
</div>
