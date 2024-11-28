@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! html_entity_decode($content) !!}
    </div>
@endsection
@pushif($unsubscribe, 'unsubscribe')
    <p><a href="{{ $unsubscribe }}">{{ __('Abonelikten Çık') }}</a></p>
@endPushIf
