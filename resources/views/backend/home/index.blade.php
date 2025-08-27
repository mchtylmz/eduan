@extends('backend.layouts.app')

@section('content')
    <!-- Overview -->
    <div class="row items-push">
        <div class="col-sm-6 col-lg-3 mb-1">
            @livewire('home.statistics', ['section' => 'users'])
        </div>
        <div class="col-sm-6 col-lg-3 mb-1">
            @livewire('home.statistics', ['section' => 'usersPremium'])
        </div>
        <div class="col-sm-6 col-lg-3 mb-1">
            @livewire('home.statistics', ['section' => 'contactMessages'])
        </div>
        {{--
        <div class="col-sm-6 col-lg-3 mb-1">
            @livewire('home.statistics', ['section' => 'examReviews'])
        </div>
        --}}
        <div class="col-12 col-sm-6 mb-1">
            @livewire('home.chart-widget', [
                'id' => 'popularAiAnswers',
                'title' => __('Popüler Yapay Zeka Konuları'),
                'subtitle' => __('Görüntüleme')
            ])
        </div>
        <div class="col-12 col-sm-6 mb-1">
            @livewire('home.chart-widget', [
                'id' => 'popularHits',
                'title' => __('En Çok Görünütülenen Testler'),
                'subtitle' => __('Görüntüleme')
            ])
        </div>
        <div class="col-12 col-sm-6 mb-1">
            @livewire('home.chart-widget', [
                'id' => 'popularResults',
                'title' => __('En Çok Yanıtlanan Testler'),
                'subtitle' => __('Yanıtlar')
            ])
        </div>
        <div class="col-12 col-sm-6 mb-1">
            @livewire('home.chart-widget', [
                'id' => 'popularLessons',
                'title' => __('En Çok Görüntülenen Dersler'),
                'subtitle' => __('Görüntüleme')
            ])
        </div>
        <div class="col-12 col-sm-6 mb-1">
            @livewire('home.user-table', ['lastLogins' => false, 'register' => true])
        </div>
        <div class="col-12 col-sm-6 mb-1">
            @livewire('home.user-table', ['lastLogins' => true, 'register' => false])
        </div>
    </div>
    <!-- END Overview -->
@endsection
@push('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        $(document).ready(function () {
            Livewire.on('chart-render', function (value) {
                let chartId = value[0]['id'] !== undefined ? value[0]['id'] : 'chart';
                let chartOptions = value[0]['options'] !== undefined ? value[0]['options'] : {};

                setTimeout(() => {
                    $('#loading_' + chartId).hide();
                    const chart = new ApexCharts(document.getElementById(chartId), chartOptions);
                    chart.render();
                }, 1000);
            });
        })
    </script>
@endpush
