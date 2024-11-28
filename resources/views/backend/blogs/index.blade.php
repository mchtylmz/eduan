@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>

            <div class="block-options">
                @can('pages:add')
                    <a href="{{ route('admin.blogs.create') }}" class="btn btn-success">
                        <i class="fa fa-fw fa-plus mx-1"></i> {{ __('Blog Ekle') }}
                    </a>
                @endcan
            </div>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            <livewire:blogs.blogs-table />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
