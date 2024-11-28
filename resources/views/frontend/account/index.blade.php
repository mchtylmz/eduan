@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAccount])
@endpush
@section('content')

    <!-- lessons area start -->
    <section class="h10_category-area pt-40 pb-50 bg-white">
        <div class="container">
            @includeIf('frontend.account.tab')

            <div class="row justify-content-between py-3">
                <div class="col-lg-6 py-2">
                    <h5 class="bg-light p-3">{{ __('Hesap Bilgilerim') }}</h5>
                    @livewire('frontend.account.profile-form')
                </div>
                <div class="col-lg-5 py-2">
                    <h5 class="bg-light p-3">{{ __('Parola Güncelle') }}</h5>
                    @livewire('frontend.account.update-password-form')
                </div>
            </div>
        </div>
    </section>
    <!-- lessons area end -->

@endsection
