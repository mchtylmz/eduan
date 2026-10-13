<div data-server-table-page="test-results" @include('components.admin.table-messages', ['confirm' => ''])>
<x-admin.filter-bar>
    <x-admin.filter-select id="result-season" name="season_id" :label="__('Dönem')" :options="$seasons" :value="activeSeason()->id ?? 0" />
    @unless($user?->exists)<x-admin.filter-select id="result-users" name="users" :label="__('Kullanıcılar')" :options="$users" multiple dropdown-size="10" />@endunless
    @if(!$test?->exists && !$incorrectCount)<x-admin.filter-select id="result-tests" name="tests" :label="__('Sınavlar')" :options="$tests" multiple dropdown-size="10" />@endif
    <x-admin.filter-select id="result-completed" name="completed" :label="__('Durum')" :options="[1 => __('Tamamlandı'), 0 => __('Tamamlanmadı')]" value="1" />
    @if($test?->exists)<input type="hidden" data-table-filter name="test_id" value="{{ $test->id }}">@endif
    @if($user?->exists)<input type="hidden" data-table-filter name="user_id" value="{{ $user->id }}">@endif
    @if($incorrectCount)<input type="hidden" data-table-filter name="incorrect_count" value="1">@endif
</x-admin.filter-bar>
<x-admin.server-table id="test-results-table" :url="route('admin.tests.getResultsData')" sort-name="completed_at" sort-order="desc" min-width="1200px">
<thead><tr>
<th data-field="id" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('ID') }}</th>
@if(!$test?->exists)<th data-field="test" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Sınav Adı') }}</th>@endif
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="email" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('E-posta Adresi') }}</th>
<th data-field="point" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Puan') }}</th>
<th data-field="question_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soru') }}</th>
<th data-field="correct_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Doğru') }}</th>
<th data-field="incorrect_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yanlış') }}</th>
<th data-field="duration" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Süre') }}</th>
<th data-field="completed" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
<th data-field="completed_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Tarih') }}</th>
<th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('Detay') }}</th>
</tr></thead>
</x-admin.server-table>
</div>
