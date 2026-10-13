@extends('backend.layouts.app')

@section('content')
    <x-admin.management-assets />
    <div class="admin-management admin-settings-layout">
        <nav class="admin-settings-nav block block-rounded" aria-label="{{ __('Site Ayarları') }}">
            <div class="admin-settings-nav-title"><i class="fa fa-sliders me-2"></i>{{ __('Site Ayarları') }}</div>
            @foreach($tabs as $tab)
                <a @class(['admin-settings-link', 'active' => $activeTab->is($tab)])
                   href="{{ route('admin.settings.index', ['activeTab' => $tab->value]) }}"
                   @if($activeTab->is($tab)) aria-current="page" @endif>
                    <i class="fa fa-fw {{ $tab->icon() ?: 'fa-link' }}" aria-hidden="true"></i>
                    <span>{{ $tab->name() }}</span>
                    @if($activeTab->is($tab))<i class="fa fa-chevron-right ms-auto small" aria-hidden="true"></i>@endif
                </a>
            @endforeach
            <div class="admin-settings-cache">
                <button type="button" class="btn btn-alt-secondary w-100 text-start"
                        onclick="if(confirm('{{ __('Önbellek temizlenecektir, işleme devam edilsin mi?') }}')) { Livewire.dispatch('runEvent', {event: 'clearCache', data: false}) }">
                    <i class="fa fa-sync me-2" aria-hidden="true"></i>{{ __('Önbellek Temizle') }}
                </button>
            </div>
        </nav>
        <section class="block block-rounded admin-settings-content" data-settings-tab="{{ $activeTab->value }}">
            <div class="block-header block-header-default">
                <h2 class="block-title"><i class="fa {{ $activeTab->icon() }} me-2 text-primary" aria-hidden="true"></i>{{ $activeTab->name() }}</h2>
            </div>
            <div class="block-content">
                @includeIf('backend.settings.tabs.' . $activeTab->value)
            </div>
        </section>
    </div>
@endsection
