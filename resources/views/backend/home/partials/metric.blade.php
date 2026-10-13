<article class="dashboard-metric dashboard-tone-{{ $tone }}">
    <div class="dashboard-metric-top">
        <span class="dashboard-icon"><i class="fa {{ $icon }}" aria-hidden="true"></i></span>
        <span class="dashboard-metric-hint">{{ $hint }}</span>
    </div>
    <strong class="dashboard-metric-value">{{ number_format($value, 0, ',', '.') }}</strong>
    @if($url)
        <a class="dashboard-metric-label" href="{{ $url }}">{{ $label }} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
    @else
        <span class="dashboard-metric-label">{{ $label }}</span>
    @endif
</article>
