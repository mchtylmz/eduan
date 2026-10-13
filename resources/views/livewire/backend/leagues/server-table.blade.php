<div data-server-table-page="leagues" @include('components.admin.table-messages', ['confirm' => __('Lig sonuçları kalıcı olarak silinecektir, işleme devam edilsin mi?')])>
<x-admin.filter-bar>
@foreach(['start_at'=>__('Başlangıç Tarihi'),'end_at'=>__('Bitiş Tarihi')] as $key=>$label)
<x-admin.filter-date :id="'leagues-'.$key" :name="$key" :label="$label" />
@endforeach
</x-admin.filter-bar>
<x-admin.server-table id="leagues-table" :url="route('admin.leagues.getData')" sort-name="id" sort-order="desc" :page-size="15" min-width="1100px">
<thead><tr><th data-field="code" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kodu') }}</th>
<th data-field="label" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Bölüm') }}</th>
<th data-field="start_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Başlangıç Tarihi') }}</th>
<th data-field="end_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Bitiş Tarihi') }}</th>
<th data-field="calculated_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Hesaplanma Tarihi') }}</th>
<th data-field="actions"  data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
</tr></thead></x-admin.server-table>
</div>