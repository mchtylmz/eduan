@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>

            <div class="block-options">
                @can('ai:add')
                    <a href="{{ route('admin.ai.images') }}" class="btn btn-success">
                        <i class="fa fa-fw fa-images mx-1"></i> {{ __('Latex Görselleri') }}
                    </a>
                @endcan
            </div>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            <livewire:ai.answer-ai-table />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
