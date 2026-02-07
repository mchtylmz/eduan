@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>

            <div class="block-options">
                @can('users:add')
                    <a href="{{ route('admin.users.create') }}" class="btn btn-success">
                        <i class="fa fa-fw fa-user-plus mx-1"></i> {{ __('Yeni Kullancıı Ekle') }}
                    </a>
                @endcan
            </div>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            @livewire('users.user-table', ['type' => $type])
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
