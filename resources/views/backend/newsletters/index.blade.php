@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>

            <div class="block-options">
                @can('lessons:add')
                    <a href="{{ route('admin.newsletter.send') }}" class="btn btn-success">
                        <i class="fa fa-fw fa-paper-plane mx-1"></i> {{ __('Bilgilendirme Gönder') }}
                    </a>
                @endcan
            </div>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            <livewire:newsletters.newsletter-table />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
