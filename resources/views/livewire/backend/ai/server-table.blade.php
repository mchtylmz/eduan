<div class="is-card-view" data-user-card-view wire:ignore.self data-server-table-page="ai" @include('components.admin.table-messages', ['confirm' => ''])>
<x-admin.filter-bar>
<x-admin.filter-select id="ai-lessons" name="lessons" :label="__('Dersler')" :options="$lessons" multiple dropdown-size="10" />
<x-admin.filter-select id="ai-topics" name="topics" :label="__('Konular')" :options="$topics" multiple dropdown-size="10" />
<x-admin.filter-select id="ai-locale" name="locale" :label="__('Dil')" :options="$languages"  />
<x-admin.filter-select id="ai-report" name="report" :label="__('Raporlama')" :options="[1 => __('Hatalı raporlananlar')]" />
<x-admin.table-sort id="ai-sort" :options="['language'=>__('Dil'),'lesson_name'=>__('Ders'),'topic_title'=>__('Konu'),'attachment'=>__('Soru'),'votes_count'=>__('Değerlendirme'),'votes_avg_vote'=>__('Ortalama'),'usages_count'=>__('Görüntüleme'),'created_at'=>__('Oluşturma'),'report'=>__('Rapor Durumu')]" sort="created_at" />
</x-admin.filter-bar>
<x-admin.server-table id="ai-table" :url="route('admin.ai.getData')" sort-name="created_at" sort-order="desc" min-width="1100px" :card-view="true">
<thead><tr>
<th data-field="language" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Dil') }}</th>
<th data-field="lesson_name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ders') }}</th>
<th data-field="topic_title" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Konu') }}</th>
<th data-field="attachment" data-sortable="true" data-formatter="eduanTableImageFormatter">{{ __('Soru') }}</th>
<th data-field="votes_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Değerlendirme') }}</th>
<th data-field="votes_avg_vote" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Ortalama') }}</th>
<th data-field="usages_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Görüntüleme') }}</th>
<th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Oluşturma') }}</th>
<th data-field="report" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Rapor') }}</th>
<th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
</tr></thead>
</x-admin.server-table>

</div>
