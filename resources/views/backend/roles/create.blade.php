@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            <!-- form -->
            <form class="js-validation" action="{{ route('admin.roles.store') }}" method="POST">

                @includeIf('backend.roles.form', ['action' => 'insert'])

                @can('roles:add')
                    <div class="mb-3 text-center py-2">
                        <button type="submit" class="btn btn-alt-primary px-4">
                            <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
                        </button>
                    </div>
                @endcan
            </form>
            <!-- form -->
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
