@extends('email.layout')
@section('subject', $subject ?? '')
@section('content')
    <div>
        {!! html_entity_decode(replaceEmailVariables($content, $user->toArray() ?? [])) !!}
    </div>
@endsection
