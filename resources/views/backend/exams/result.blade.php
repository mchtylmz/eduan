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
            <div class="bg-body-light py-2 px-3 fw-bold fs-6 mb-1">
                ({{ str($exam->code)->upper() }}) {{ $exam->name }}
            </div>
            <livewire:exams.results-table :exam="$exam" />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
