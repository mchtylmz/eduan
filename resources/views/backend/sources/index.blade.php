@extends('backend.layouts.app')
@section('content')
    <x-admin.management-assets />
    <div class="block block-rounded admin-management" data-source-manager
         data-prepare="{{ route('admin.files.prepare') }}" data-finish="{{ route('admin.files.finish', ['code' => 'SOURCE_CODE']) }}"
         data-messages='@json(__('sources'))'>
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>
            <div class="block-options">@can('files:add')<button class="btn btn-success" data-bs-toggle="offcanvas" data-bs-target="#source-upload"><i class="fa fa-upload me-1"></i>{{ __('sources.upload') }}</button>@endcan</div>
        </div>
        <div class="block-content pb-3 fs-sm">
            <div data-server-table-page="files" @include('components.admin.table-messages', ['confirm' => __('sources.confirm_delete')])>
                <x-admin.filter-bar>
                    <x-admin.filter-select id="source-type" name="type" :label="__('sources.type')" :options="array_combine(array_keys(\App\Helpers\Sources\SourceSettings::TYPES), array_map('strtoupper', array_keys(\App\Helpers\Sources\SourceSettings::TYPES)))" />
                </x-admin.filter-bar>
                <x-admin.server-table id="source-files-table" :url="route('admin.files.data')" sort-name="created_at" sort-order="desc">
                    <thead><tr>
                        @foreach(['title','name','type','size','uploader','created_at','status'] as $field)
                            <th data-field="{{ $field }}" data-class="{{ $field === 'size' ? 'text-nowrap' : '' }}" data-sortable="{{ in_array($field, ['title','size','created_at']) ? 'true':'false' }}" data-formatter="eduanTableTextFormatter">{{ __('sources.'.$field) }}</th>
                        @endforeach
                        <th data-field="actions" data-formatter="eduanSourceActionsFormatter">{{ __('sources.actions') }}</th>
                    </tr></thead>
                </x-admin.server-table>
            </div>
            <p class="text-danger mt-2" data-source-list-error role="alert"></p>
        </div>
        @can('files:add')
        <div class="offcanvas offcanvas-end w-100" style="max-width:640px" tabindex="-1" id="source-upload" aria-labelledby="source-upload-title">
            <div class="offcanvas-header bg-body-light"><h3 class="offcanvas-title fs-5" id="source-upload-title">{{ __('sources.upload') }}</h3><button class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Kapat') }}"></button></div>
            <div class="offcanvas-body">
                <form data-source-upload-form>
                    <label class="form-label" for="source-title">{{ __('sources.title_field') }}</label>
                    <input class="form-control mb-3" id="source-title" name="title" required maxlength="180">
                    <label class="form-label" for="source-file">{{ __('sources.file') }}</label>
                    <input class="form-control" id="source-file" name="file" type="file" required accept="{{ '.'.implode(',.', \App\Helpers\Sources\SourceSettings::types()) }}">
                    <p class="form-text">{{ __('sources.upload_help', ['size' => \App\Helpers\Sources\SourceSettings::maxBytes()/1024/1024]) }}</p>
                    <progress class="w-100" max="100" value="0" data-source-progress aria-label="{{ __('sources.progress') }}"></progress>
                    <p class="mt-2" data-source-status role="status" aria-live="polite"></p>
                    <div class="alert alert-danger d-none" data-source-error role="alert"></div>
                    <button type="button" class="btn btn-warning w-100 my-3 d-none" data-source-fallback>{{ __('sources.fallback') }}</button>
                    <button class="btn btn-primary w-100" type="submit" data-source-submit><i class="fa fa-upload me-1"></i>{{ __('sources.upload') }}</button>
                    <div class="mt-4 d-none" data-source-success>
                        <label class="form-label">{{ __('sources.link') }}</label><input class="form-control mb-2" readonly data-source-link>
                        <button type="button" class="btn btn-alt-primary" data-source-copy>{{ __('sources.copy') }}</button>
                        <a class="btn btn-info" target="_blank" rel="noopener" data-source-preview>{{ __('sources.view') }}</a>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    </div>
@endsection
@push('script')
    <script src="{{ asset('backend/assets/source-files.js') }}?v={{ config('app.version') }}"></script>
@endpush
