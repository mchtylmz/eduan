@extends('frontend.layouts.app')
@section('content')
    <section class="py-3 bg-light border-bottom sticky-top">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h1 class="h5 mb-0">{{ $source->title }}</h1>
            <a class="btn btn-primary" href="{{ $returnPath }}"><i class="fa fa-arrow-left me-1" aria-hidden="true"></i>{{ __($label) }}</a>
        </div>
    </section>
    <div class="container-fluid py-3">
        @if(in_array($source->extension, ['pdf','html'], true))
            <iframe class="w-100 border rounded" style="height:80vh" title="{{ $source->title }}"
                    @if($source->extension === 'html') sandbox referrerpolicy="no-referrer" @endif
                    src="{{ $source->extension === 'html' ? route('frontend.sources.html', $source->code) : $source->rawUrl() }}"></iframe>
        @else
            <img class="img-fluid d-block mx-auto" src="{{ $source->rawUrl() }}" alt="{{ $source->title }}">
        @endif
        <p class="small text-center mt-3"><a href="{{ $source->rawUrl() }}" target="_blank" rel="noopener">{{ __('sources.original') }}</a></p>
    </div>
@endsection
