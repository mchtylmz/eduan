<div data-server-table-page="league-results" data-table-url-state="false" @include('components.admin.table-messages', ['confirm' => __('')])>
<x-admin.filter-bar>
<x-admin.filter-select id="league-users" name="users" :label="__('Kullanıcılar')" :options="$users" multiple dropdown-size="10" />
<x-admin.filter-select id="league-number" name="league_number" :label="__('Lig')" :options="collect(range(1,7))->mapWithKeys(fn($number)=>[$number=>__($number.'. Lig')])->all()" />
<input type="hidden" name="league_id" data-table-filter value="{{ $leagueId }}"></x-admin.filter-bar>
<x-admin.server-table id="league-results-table" :url="route('admin.leagues.getResultsData')" sort-name="league_number" sort-order="asc" :page-size="5" min-width="780px">
<thead><tr><th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="total_correct" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Doğru') }}</th>
<th data-field="total_incorrect" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yanlış') }}</th>
<th data-field="league_number" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Lig') }}</th>
<th data-field="position" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Sıra') }}</th>
<th data-field="total_score" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Puan') }}</th>
</tr></thead></x-admin.server-table>
</div>