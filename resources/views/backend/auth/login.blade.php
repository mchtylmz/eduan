@extends('backend.layouts.app')

@section('content')
    <!-- Sign In Section -->
    <div class="bg-body-extra-light">
        <div class="content content-full">
            <div class="row g-0 justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-4 py-4 px-4 px-lg-5">
                    @if($logo = settings()->siteLogo)
                        <!-- Header -->
                        <div class="text-center">
                            <p class="mb-3">
                                <img src="{{ asset($logo) }}" class="login-logo" alt="logo"/>
                            </p>
                        </div>
                        <!-- END Header -->
                    @endif

                    @livewire('auth.login')
                </div>
            </div>
        </div>
    </div>
    <!-- END Sign In Section -->
@endsection
