<div data-server-table-page="ai-votes" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
<x-admin.filter-bar>
<x-admin.filter-select id="vote-users" name="users" :label="__('Kullanıcılar')" :options="$users" multiple dropdown-size="10" />
<x-admin.filter-date id="ai-votes-date" name="date" :label="__('Tarih')" />
<input type="hidden" name="answer_ai_id" data-table-filter value="{{ $answerAiId }}"></x-admin.filter-bar>
<x-admin.server-table id="ai-votes-table" :url="route('admin.ai.getVotesData')" sort-name="created_at" sort-order="desc" :page-size="15" min-width="1100px">
<thead><tr><th data-field="username" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th>
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="vote" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Değerlendirme') }}</th>
<th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Değerlendirme Zamanı') }}</th>
<th data-field="actions"  data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
</tr></thead></x-admin.server-table>
</div>