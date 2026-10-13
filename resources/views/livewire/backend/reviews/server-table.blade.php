<div data-server-table-page="reviews" @include('components.admin.table-messages', ['confirm' => __('Kayıt silinecektir, işleme devam edilsin mi?')])>
    <x-admin.filter-bar>
        @if(!$user?->exists)<x-admin.filter-select id="review-users" name="users" :label="__('Kullanıcılar')" :options="$users" multiple />@endif
        @if(!$exam?->exists)<x-admin.filter-select id="review-exams" name="exams" :label="__('Testler')" :options="$exams" multiple />@endif
        <x-admin.filter-select id="reviews-has-read" name="has_read" :label="__('Durum')" :options="[1 => __('Okundu'), 0 => __('Okunmamış')]" multiple />
    </x-admin.filter-bar>
    <x-admin.server-table id="reviews-table" :url="route('admin.exams.getReviewsData', ['exam_id' => $exam?->id, 'user_id' => $user?->id])" sort-name="created_at" sort-order="desc" min-width="1100px">
        <thead><tr>
            <th data-field="exam" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Test Adı') }}</th>
            <th data-field="name" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('İsim') }}</th>
            <th data-field="surname" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Soyisim') }}</th>
            <th data-field="comment" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Değerlendirme') }}</th>
            <th data-field="visibility" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Görünüm') }}</th>
            <th data-field="has_read" data-sortable="true" data-formatter="eduanTableStatusFormatter">{{ __('Durum') }}</th>
            <th data-field="created_at" data-sortable="true" data-formatter="eduanTableTextFormatter">{{ __('Kayıt Tarihi') }}</th>
            <th data-field="actions" data-formatter="eduanTableActionsFormatter" data-align="right">{{ __('İşlemler') }}</th>
        </tr></thead>
    </x-admin.server-table>
    <x-admin.table-detail id="reviews-detail" :title="__('tables.reviews_detail')" :details="$details" :edit-url="$editUrl" />
</div>
