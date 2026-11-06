@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! $content !!}
    </div>
@endsection
