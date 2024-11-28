@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! html_entity_decode(replaceEmailVariables($content, $user->toArray() ?? [])) !!}
    </div>

    <a href="{{ route('frontend.verify', $user->email_verified_token ?? 0) }}" class="email-button">
        {{ __('Hesabımı Doğrula') }}
    </a>
    <p>{{ __('Butona tıklayamıyorsanız, aşağıdaki bağlantıyı kopyalayıp tarayıcınıza yapıştırabilirsiniz:') }}</p>
    <small style="word-break: break-all;" class="plan-link">
        {{ route('frontend.verify', $user->email_verified_token ?? 0) }}
    </small>
@endsection
