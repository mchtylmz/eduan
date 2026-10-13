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
            <livewire:contacts.contact-table />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
