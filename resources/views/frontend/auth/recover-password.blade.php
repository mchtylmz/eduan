@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAuth])
@endpush
@section('content')

    <!-- sign in area start -->
    <div class="account-area pt-40 pb-50">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-6 col-md-6">
                    <div class="account-wrap">
                        <div class="account-main p-3">
                            @if(!settings()->coverAuth)
                                <h3 class="account-title mb-2">{{ $title }}</h3>
                            @endif
                            <p class="fw-bold">{{ __('Parolanızı sıfırlama talimatlarını içeren bir e-posta göndereceğiz.') }}</p>

                                @livewire('frontend.auth.recover-password')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- sign in area end -->

@endsection
