@extends('backend.layouts.app')
@section('content')
<div class="block block-rounded"><div class="block-header block-header-default"><h1 class="block-title">{{ $title }}</h1></div><div class="block-content pb-4"><livewire:backend.communications.communication-form /></div></div>
@endsection
