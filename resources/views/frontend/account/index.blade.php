@extends('frontend.account.layout')
@section('account-content')
    <div class="row g-3 member-forms">
        <div class="col-xl-7">
            <section class="member-form-card" aria-labelledby="member-details-title">
                <header class="member-form-heading">
                    <span class="member-nav-icon"><i class="fa-light fa-user-pen" aria-hidden="true"></i></span>
                    <div><h2 id="member-details-title">{{ __('account.details') }}</h2></div>
                </header>
                <div class="member-form-body">@livewire('frontend.account.profile-form')</div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="member-form-card" aria-labelledby="member-security-title">
                <header class="member-form-heading">
                    <span class="member-nav-icon"><i class="fa-light fa-lock-keyhole" aria-hidden="true"></i></span>
                    <div><h2 id="member-security-title">{{ __('account.security') }}</h2><p>{{ __('account.security_hint') }}</p></div>
                </header>
                <div class="member-form-body">@livewire('frontend.account.update-password-form')</div>
            </section>
        </div>
    </div>
@endsection
