<div data-server-table-page="ai-latex-images" @include('components.admin.table-messages', ['confirm' => __('Latex görseli silinecektir, işleme devam edilsin mi?')])>
    <x-admin.server-table id="ai-latex-images-table" :url="route('admin.ai.getImagesData')" sort-name="id" :page-size="15" min-width="1000px">
        <thead>
            <tr>
                <th data-field="formula" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Formül') }}</th>
                <th data-field="image" data-sortable="true" data-formatter="eduanTableLatexImageFormatter">{{ __('Görsel') }}</th>
                <th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Oluşturma Zamanı') }}</th>
                <th data-field="actions" data-formatter="eduanTableActionsFormatter">{{ __('İşlemler') }}</th>
            </tr>
        </thead>
    </x-admin.server-table>
</div>
