@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default flex-column flex-md-row align-items-start align-items-md-center gap-2">
            <h3 class="block-title">{{ $title }}</h3>

            <div class="block-options">
                @can('leagues:run')
                    <button type="button" class="btn btn-success"
                            onclick="Livewire.dispatch('showOffcanvas', {component: 'leagues.league-run-form', data: {title: '{{ __('leagues.create') }}'}})">
                        <i class="fa fa-fw fa-sync opacity-50 me-1"></i>
                        {{ __('leagues.create') }}
                    </button>
                @endcan
            </div>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            <livewire:leagues.league-table />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
