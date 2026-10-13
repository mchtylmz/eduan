@php
    $series = $kind === 'results'
        ? ['question_count' => __('Soru Sayısı'), 'correct_count' => __('Doğru'), 'incorrect_count' => __('Yanlış')]
        : ['hits' => $metric];
    $maximum = max([1, ...array_map(fn ($row) => max(array_map(fn ($key) => (int) $row[$key], array_keys($series))), $rows)]);
    $scale = max(1, (int) ceil($maximum / 4)) * 4;
@endphp
<section class="dashboard-panel" data-dashboard-ranking="{{ $kind }}">
    <header class="dashboard-panel-header">
        <span class="dashboard-icon"><i class="fa {{ $icon }}" aria-hidden="true"></i></span>
        <div><h2 id="dashboard-chart-{{ $kind }}">{{ $title }}</h2><p>{{ $metric }} · {{ __('dashboard.top_five') }}</p></div>
    </header>
    @if($rows)
        <figure class="dashboard-chart dashboard-chart-{{ $kind }}" aria-labelledby="dashboard-chart-{{ $kind }}">
            <figcaption class="dashboard-chart-legend">
                @foreach($series as $key => $label)
                    <span><i class="dashboard-chart-key dashboard-series-{{ $key }}" aria-hidden="true"></i>{{ $label }}</span>
                @endforeach
            </figcaption>
            @foreach($rows as $row)
                <div class="dashboard-chart-category">
                    <div class="dashboard-chart-name">{{ $row['name'] ?: __('dashboard.deleted_record') }}</div>
                    <div class="dashboard-chart-plot">
                        @foreach($series as $key => $label)
                            <div class="dashboard-chart-lane dashboard-series-{{ $key }}" title="{{ $label }}: {{ number_format($row[$key], 0, ',', '.') }}">
                                <span class="dashboard-chart-bar" style="width: {{ round(max(0, $row[$key]) / $scale * 100, 3) }}%" aria-hidden="true"></span>
                                <span class="dashboard-chart-value"><span class="visually-hidden">{{ $label }}: </span>{{ number_format($row[$key], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </figure>
    @else
        <div class="dashboard-empty"><i class="fa fa-chart-simple" aria-hidden="true"></i><span>{{ __('dashboard.empty') }}</span></div>
    @endif
</section>
