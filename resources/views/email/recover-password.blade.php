@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! html_entity_decode(replaceEmailVariables($content, $user->toArray() ?? [])) !!}
    </div>

    <a href="{{ route('frontend.create.password', $token) }}" class="email-button">
        {{ __('Parolamı Sıfırla') }}
    </a>
    <p>{{ __('Butona tıklayamıyorsanız, aşağıdaki bağlantıyı kopyalayıp tarayıcınıza yapıştırabilirsiniz:') }}</p>
    <small style="word-break: break-all;" class="plan-link">
        {{ route('frontend.create.password', $token) }}
    </small>
    <p>{{ __('Eğer bu işlemi siz yapmadıysanız, lütfen bu e-posta hakkında işlem yapmayınız. Hesabınız güvende kalacaktır.') }}</p>
@endsection
