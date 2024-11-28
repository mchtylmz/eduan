@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAuth])
@endpush
@section('content')

    <!-- sign in area start -->
    <div class="account-area pt-40 pb-50">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8 col-md-10">
                    <div class="account-wrap">
                        <div class="account-main p-3">
                            @if(!settings()->coverAuth)
                                <h3 class="account-title">{{ $title }}</h3>
                            @endif

                            @livewire('frontend.auth.login')

                            @if(settings()->registerStatus == \App\Enums\StatusEnum::ACTIVE->value)
                                <div class="account-break">
                                    <span>VEYA</span>
                                </div>
                                <div class="account-bottom">
                                    <div class="account-option">
                                        <a href="{{ route('frontend.register') }}"
                                           class="account-option-account text-dark border-3 border-dark">
                                            <i class="fa fa-user-plus me-1 bg"></i>
                                            <span class="fw-medium fs-6">{{ __('Yeni Üyelik Oluştur') }}</span>
                                        </a>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- sign in area end -->

@endsection
