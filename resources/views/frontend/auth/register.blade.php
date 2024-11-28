@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAuth])
@endpush
@section('content')

    <!-- sign in area start -->
    <div class="account-area pt-25 pb-50">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8 col-md-10 p-3">
                    @if(!settings()->coverAuth)
                        <h3 class="account-title">{{ __('Yeni Üyelik Oluştur') }}</h3>
                    @endif
                    @livewire('frontend.auth.register')
                </div>
            </div>
        </div>
    </div>
    <!-- sign in area end -->

@endsection
