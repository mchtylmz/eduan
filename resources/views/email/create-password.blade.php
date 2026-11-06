@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! replaceEmailVariables($content, $user->toArray() ?? []) !!}
    </div>
@endsection
