@extends('frontend.layouts.app')
@push('seo')
    @includeIf('components.seo.tags', [
        'title' => $title,
        'image' => settings()->coverContact
    ])
@endpush
@section('content')
    <!-- lessons area start -->
    <section class="h10_category-area pt-30 pb-50 bg-white">
        <div class="container px-3 px-sm-2">
            @livewire('frontend.leagues.league-table', ['selectedLeague' => $league ?? null])
        </div>
    </section>
    <!-- lessons area end -->
@endsection
@push('script')
    <script>
        $(document).on('click', 'a.choose-league', function () {
            $('.choose-league-loading').removeClass('d-none');
        });
    </script>
@endpush
