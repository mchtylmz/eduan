@extends('backend.layouts.app')

@section('content')
    <!-- block -->
    <div class="block block-rounded">
        <ul class="nav nav-tabs nav-tabs-alt block-header-default" role="tablist">
            <li class="nav-item" role="presentation">
                <a @class(['nav-link py-3', 'active' => $activeTab == 'stats']) type="button" href="?tab=stats">
                    <i class="fa fa-percentage mx-1"></i> {{ __('Sonuç İstatistikleri') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a @class(['nav-link py-3', 'active' => $activeTab == 'corrects']) type="button" href="?tab=corrects">
                    <i class="fa fa-check-double mx-1"></i> {{ __('Hepsi Doğru Cevaplanan Testler') }}
                </a>
            </li>
        </ul>

        <div class="block-content fs-sm pb-3">
            <div class="tab-pane active show" tabindex="0">
                @includeIf(sprintf('backend.stats.tab.%s', $activeTab))
            </div>

        </div>
        <!-- block-content -->
    </div>
    <!-- block -->
@endsection
