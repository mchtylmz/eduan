@extends('frontend.account.layout')
@section('account-content')
    @livewire('frontend.account.my-league-result-table', ['userId' => auth()->id()])
@endsection
