@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <!-- block-header -->
        <div class="block-header block-header-default">
            <h3 class="block-title">({{ str($language->code)->upper() }}) {{ $language->name }} {{ $title }}</h3>
        </div>
        <!-- block-content -->
        <div class="block-content fs-sm pb-3">
            <livewire:languages.language-translate-form :languageId="$language->id" />
        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection

