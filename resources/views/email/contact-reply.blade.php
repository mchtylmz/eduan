@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! html_entity_decode($content) !!}
    </div>

    <br>
    <hr>
    <ul style="list-style: none">
        <li>{{ __('İsim Soyisim') }}: {{ $contact->name ?? '' }}</li>
        <li>{{ __('E-posta Adresi') }}: {{ $contact->email ?? '' }}</li>
        <li>{{ __('Telefon') }}: {{ $contact->phone ?? '' }}</li>
        <li>{{ __('Okul Adı') }}: {{ $contact->school_name ?? '' }}</li>
        <li>{{ __('Mesajınız') }}: {{ $contact->message ?? '' }}</li>
    </ul>
@endsection
