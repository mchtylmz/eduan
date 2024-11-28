@extends('backend.layouts.app')

@section('content')

    <div class="block block-rounded row g-0">
        <ul class="nav nav-tabs nav-tabs-block flex-md-column col-md-3 d-grid d-sm-block" role="tablist">
            <!-- tabs -->
            @foreach($tabs as $tab)
                <li class="nav-item d-md-flex flex-md-column" role="presentation">
                    <a type="button"
                       @class(['nav-link text-md-start p-3 border w-100','active' => $activeTab->is($tab)])
                       href="?activeTab={{ $tab->value }}">
                        <i class="fa fa-fw {{ $tab->icon() ?: 'fa-link' }} opacity-50 me-1"></i>
                        {!! $tab->name() !!}
                    </a>
                </li>
            @endforeach
            <!-- /tabs -->
            <!-- cache -->
            <li class="nav-item d-md-flex flex-md-column" role="presentation">
                <button type="button" class="nav-link text-md-start p-3 border w-100"
                        onclick="if(confirm('{{ __('Önbellek temizlenecektir, işleme devam edilsin mi?') }}')) { Livewire.dispatch('runEvent', {event: 'clearCache', data: false}) }">
                    <i class="fa fa-fw fa-sync opacity-50 me-1"></i>
                    {{ __('Önbellek Temizle') }}
                </button>
            </li>
            <!-- /cache -->
        </ul>
        <div class="tab-content col-md-9">
            <!-- tabs -->
            <div class="block-content tab-pane active show p-3">
                @includeIf('backend.settings.tabs.' . $activeTab->value)
            </div>
            <!-- /tabs -->
        </div>
    </div>
@endsection
