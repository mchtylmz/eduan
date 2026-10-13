<div data-server-table-page="result-stats" @include('components.admin.table-messages', ['confirm' => __('')])>
<x-admin.filter-bar>
@if(!$user?->exists)<x-admin.filter-select id="stats-users" name="user_id" :label="__('Kullanıcılar')" :options="$users" />
@else<input type="hidden" name="context_user_id" data-table-filter value="{{ $user->id }}"><input type="hidden" name="user_id" data-table-filter value="{{ $user->id }}">@endif
<x-admin.filter-select id="stats-type" name="result_type" :label="__('Sonuç Türü')" :options="['topics'=>__('Konu'),'exams'=>__('Test'),'tests'=>__('Sınav')]" />
<x-admin.filter-select id="stats-percent" :allow-all="false" name="result_percent" :label="__('Sonuç Kriteri')" :options="collect(range(10,100,10))->mapWithKeys(fn($p)=>[$p=>'%'.$p.' '.__('ve altında')])->all()" value="100" />
<x-admin.filter-select id="stats-topic" name="topic_id" :label="__('Konu')" :options="$topics" />
</x-admin.filter-bar>
<div class="d-flex flex-wrap gap-2 mb-3">
<a class="btn btn-success" data-table-export href="{{ $user?->exists ? route('admin.users.getStatsExport') : route('admin.stats.getExport') }}"><i class="fa fa-file-excel me-1"></i>{{ __('Excele Aktar') }}</a>
@can('users:send-email')<button type="button" data-table-mail class="btn btn-info"><i class="fa fa-at me-1"></i>{{ __('E-posta Gönder') }}</button>@endcan
</div>
<x-admin.server-table id="result-stats-table" :url="$user?->exists ? route('admin.users.getStatsData') : route('admin.stats.getData')" sort-name="name" sort-order="asc" :page-size="15" min-width="1100px">
<thead><tr><th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="content_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Sonuç') }}</th>
<th data-field="point" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Puan') }}</th>
<th data-field="total_questions" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Toplam Soru') }}</th>
<th data-field="count_correct" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Doğru Yanıt') }}</th>
<th data-field="count_incorrect" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yanlış Yanıt') }}</th>
<th data-field="success_rate" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Başarı Yüzdesi') }}</th>
</tr></thead></x-admin.server-table>
</div>