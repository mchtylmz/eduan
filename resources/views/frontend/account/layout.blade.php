@extends('frontend.layouts.app')
@section('content')
    <section class="member-page pt-30 pb-50">
        <div class="container">
            <div class="row g-4 member-layout">
                <aside class="col-md-3 member-sidebar">
                    @include('frontend.account.tab')
                </aside>
                <div class="col-md-9 member-content">
                    <h1 class="member-page-title">{{ $title ?? __('account.profile') }}</h1>
                    @yield('account-content')
                </div>
            </div>
        </div>
    </section>
@endsection
