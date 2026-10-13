<div data-server-table-page="question-stats" @include('components.admin.table-messages', ['confirm' => __('')])>
<x-admin.filter-bar>
@foreach(['started_at'=>__('Başlangıç Tarihi'),'ended_at'=>__('Bitiş Tarihi')] as $key=>$label)
<x-admin.filter-date :id="'question-stats-'.$key" :name="$key" :label="$label" :value="$this->$key" />
@endforeach
</x-admin.filter-bar>
<a class="btn btn-success mb-3" data-table-export href="{{ route('admin.stats.getQuestionsExport') }}"><i class="fa fa-file-excel me-1"></i>{{ __('Excele Aktar') }}</a>
<x-admin.server-table id="question-stats-table" :url="route('admin.stats.getQuestionsData')" sort-name="question_count" sort-order="desc" :page-size="15" min-width="1100px">
<thead><tr><th data-field="username" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kullanıcı Adı') }}</th>
<th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
<th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
<th data-field="question_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Toplam Soru') }}</th>
<th data-field="correct_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Doğru Yanıt') }}</th>
<th data-field="incorrect_count" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Yanlış Yanıt') }}</th>
<th data-field="success_rate" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Başarı Yüzdesi') }}</th>
<th data-field="updated_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Son Güncelleme') }}</th>
</tr></thead></x-admin.server-table>
</div>