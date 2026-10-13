<div data-server-table-page="exam-favorites" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => ''])>
<x-admin.filter-bar><input type="hidden" name="exam_id" data-table-filter value="{{ $record->id }}"></x-admin.filter-bar>
<x-admin.server-table id="exam-favorites-table" :url="route('admin.exams.getFavoritesData')" sort-name="username" :page-size="5" min-width="760px">
<thead><tr>
<th data-field="username" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th>
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="last_login_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Son Giriş') }}</th>
</tr></thead>
</x-admin.server-table>
</div>
