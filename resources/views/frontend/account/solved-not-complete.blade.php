@extends('frontend.account.layout')
@section('account-content')
    @livewire('frontend.account.solved-tests', ['showNotComplete' => true])
@endsection
