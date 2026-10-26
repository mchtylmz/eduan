<div>
    @if($report)
        @if($activeTab === 'overview')
            @include('livewire.frontend.study.weekly-evaluation', ['evaluationTitle' => __('study.weekly_report_panel'), 'evaluationChartTitle' => __('study.goal_actual'), 'evaluationHelp' => __('study.panel_summary_help')])
        @elseif($activeTab === 'history')
<div class="block block-rounded"><div class="block-header block-header-default"><h2 class="block-title">{{ __('study.four_weeks') }}</h2></div><div class="block-content pb-3 row g-4">
@foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'] as $field => $label)
@php
    $scale = max(1, collect($report['trend'])->max(fn ($r) => max($r['actual'][$field], $r['target'][$field] ?? 0)));
@endphp
<div class="col-md-4"><h3 class="h6">{{ __('study.'.$label) }}</h3>
@foreach($report['trend'] as $row)
<div class="mb-3"><div class="small mb-1">{{ \App\Helpers\StudyCalendar::label($row['start'], 'j M') }} — {{ \App\Helpers\StudyCalendar::label($row['end'], 'j M Y') }}</div>
@foreach(['target' => 'goal', 'actual' => 'actual'] as $source => $series)
@php
    $value = $row[$source][$field] ?? null;
@endphp
<div class="d-flex justify-content-between small"><span>{{ __('study.'.$series) }}</span><strong>{{ $field === 'minutes' ? (\App\Helpers\StudyInput::hours($value) ?: '—') : ($value ?? '—') }}</strong></div>
<div class="progress mb-1" style="height:6px" role="progressbar" aria-label="{{ __('study.'.$label).' · '.__('study.'.$series) }}" aria-valuenow="{{ $value ?? 0 }}" aria-valuemin="0" aria-valuemax="{{ $scale }}"><div class="progress-bar {{ $source === 'target' ? 'bg-secondary' : 'bg-primary' }}" style="width:{{ round(100 * ($value ?? 0) / $scale, 2) }}%"></div></div>
@endforeach</div>
@endforeach</div>
@endforeach</div></div>

            <section class="block block-rounded"><header class="block-header block-header-default"><div><h2 class="block-title">{{ __('study.history_title_panel') }}</h2><p class="small text-muted mb-0 mt-1">{{ __('study.history_help') }}</p></div></header><div class="block-content pb-3">
                @include('livewire.backend.study.detail-table', ['type' => 'history'])
            </div></section>
        @elseif($activeTab === 'activity')
            <section class="block block-rounded"><header class="block-header block-header-default"><div><h2 class="block-title">{{ __('study.daily_totals') }}</h2><p class="small text-muted mb-0 mt-1">{{ __('study.combined_help') }}</p></div></header><div class="block-content pb-3">
                <div class="study-panel-totals mb-3"><strong>{{ __('study.week_total') }}</strong>@foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'] as $field => $label)<span>{{ __('study.'.$label) }}: <b>{{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($report['current']['actual'][$field]) : $report['current']['actual'][$field] }}</b></span>@endforeach</div>
                @include('livewire.backend.study.detail-table', ['type' => 'days'])
            </div></section>
            <section class="block block-rounded"><header class="block-header block-header-default"><div><h2 class="block-title">{{ __('study.manual_entries') }}</h2><p class="small text-muted mb-0 mt-1">{{ __('study.fragments_help') }}</p></div></header><div class="block-content pb-3">
                @include('livewire.backend.study.detail-table', ['type' => 'entries'])
            </div></section>
        @elseif($activeTab === 'analysis')
            <section class="block block-rounded"><header class="block-header block-header-default"><div><h2 class="block-title">{{ __('study.topic_analysis') }}</h2><p class="small text-muted mb-0 mt-1">{{ __('study.analysis_simple', ['minimum' => \App\Helpers\StudyTrackingSettings::minimumTopicQuestions()]) }}</p></div></header><div class="block-content pb-3">
                @include('livewire.backend.study.detail-table', ['type' => 'topics'])
            </div></section>
        @if($report['comparisons'])
            <section class="block block-rounded" >
                <header class="block-header block-header-default"><div><h2 class="block-title">{{ __('study.comparison') }}</h2><p>{{ __('study.comparison_help') }}</p></div></header>
                <div class="block-content pb-3 row g-4">
                    @foreach($report['comparisons'] as $group => $scores)
                        <div class="col-lg-6"><h3 class="h6">{{ __('study.'.$group) }}</h3>
                            <dl class="mb-0">
                                @foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours', 'achievement' => 'overall', 'accuracy' => 'accuracy'] as $metric => $label)
                                    <dt class="small mt-2">{{ __('study.'.$label) }}</dt><dd class="small mb-0">@if($score = $scores[$metric]){{ __('study.top_percent', ['percent' => $score['top']]) }} · {{ __('study.lower_percent', ['percent' => $score['lower']]) }} · {{ __('study.group_size', ['count' => $score['size']]) }}@else{{ __('study.no_comparison') }}@endif</dd>
                                @endforeach
                            </dl>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
        @elseif($activeTab === 'notes')
            <section class="block block-rounded"><header class="block-header block-header-default"><div><h2 class="block-title">{{ __('study.notes') }}</h2><p class="small text-muted mb-0 mt-1">{{ __('study.notes_help') }}</p></div></header><div class="block-content pb-3">
                @include('livewire.backend.study.detail-table', ['type' => 'notes'])
            </div></section>
        @endif
    @else<div class="alert alert-info">{{ __('study.disabled') }}</div>@endif
</div>
