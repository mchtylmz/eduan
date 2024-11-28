@extends('frontend.layouts.app')
@push('breadcrumb')
    @includeIf('frontend.layouts.section.page-breadcrumb', ['coverImage' => settings()->coverAuth])
@endpush
@section('content')

    <!-- sign in area start -->
    <div class="account-area pt-40 pb-50">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-7 col-md-7">
                    <div class="account-wrap">
                        <div class="account-main p-3">
                            @if(!settings()->coverAuth)
                                <h3 class="account-title">{{ $title }}</h3>
                            @endif

                            @livewire('frontend.auth.create-password', ['user' => $user])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- sign in area end -->

@endsection
