@extends('backend.layouts.app')

@section('content')
    <x-admin.management-assets />
    <!-- block -->
    <div class="block block-rounded admin-management">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">{{ $title }}</h3>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            @if(isset($exam))
                <div class="bg-body-light py-2 px-3 fw-bold fs-6 mb-3">({{ str($exam->code)->upper() }}) {{ $exam->name }}</div>
            @endif
            <livewire:exams.reviews-table :exam="$exam ?? null" />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
