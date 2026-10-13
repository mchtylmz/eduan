<div data-server-table-page="ai-usages" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
<x-admin.filter-bar>
<x-admin.filter-date id="ai-usages-date" name="date" :label="__('Tarih')" />
<input type="hidden" name="answer_ai_id" data-table-filter value="{{ $answerAiId }}"></x-admin.filter-bar>
<x-admin.server-table id="ai-usages-table" :url="route('admin.ai.getUsagesData')" sort-name="id" sort-order="desc" :page-size="15" min-width="1100px">
<thead><tr><th data-field="username" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th>
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="usage" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanım') }}</th>
<th data-field="remaining" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kalan') }}</th>
<th data-field="usage_date" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanım Zamanı') }}</th>
<th data-field="actions"  data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
</tr></thead></x-admin.server-table>
</div>