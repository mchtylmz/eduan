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
            <livewire:faqs.faq-form :faqId="$faq->id" />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
