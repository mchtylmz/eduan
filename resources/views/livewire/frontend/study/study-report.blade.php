@php($panel = $this instanceof \App\Livewire\Backend\Study\StudentReport)
<div class="mb-4">
    @if($report)
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}" @unless($panel) id="study-week-content" x-show="studySection === 'week'" @endunless>
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __($panel ? 'study.weekly_report_panel' : 'study.weekly_report') }}</h2><p>{{ __($panel ? 'study.rates_help' : 'study.week_summary_help') }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <div class="row g-4">
                    @foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'] as $field => $label)
                        @php($rate = $report['current']['rates'][$field])
                        @php($goalText = isset($report['current']['target'][$field]) ? ($field === 'minutes' ? \App\Helpers\StudyInput::hours($report['current']['target'][$field]) : $report['current']['target'][$field]) : '—')
                        <div class="col-md-4">
                            <h3 class="h6">{{ __('study.'.$label) }}</h3>
                            <div class="d-flex justify-content-between gap-2 mb-2"><strong>{{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($report['current']['actual'][$field]) : $report['current']['actual'][$field] }}</strong><span>{{ __('study.goal') }}: {{ $goalText }}</span></div>
                            @unless($panel)
                                @php($weekScale = max(1, $report['current']['actual'][$field], $report['current']['target'][$field] ?? 0))
                                <div class="study-week-chart" role="img" aria-label="{{ __('study.'.$label) }}: {{ __('study.goal') }} {{ $goalText }}, {{ __('study.actual') }} {{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($report['current']['actual'][$field]) : $report['current']['actual'][$field] }}">
                                    <div><span>{{ __('study.goal') }}</span><div class="progress"><div class="progress-bar bg-secondary" style="width:{{ round(100 * ($report['current']['target'][$field] ?? 0) / $weekScale, 2) }}%"></div></div></div>
                                    <div><span>{{ __('study.actual') }}</span><div class="progress"><div class="progress-bar study-chart-actual" style="width:{{ round(100 * $report['current']['actual'][$field] / $weekScale, 2) }}%"></div></div></div>
                                </div>
                            @endunless
                            @if($rate !== null)
                                <div @class(['progress', 'd-none' => ! $panel]) role="progressbar" aria-label="{{ __('study.'.$label) }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, $rate) }}" aria-valuetext="{{ $rate }}%"><div class="progress-bar" style="width:{{ min(100, $rate) }}%; background-color:#34317d"></div></div>
                                <div class="small study-rate">{{ $rate }}% @if($rate > 100)<span>{{ __('study.exceeded') }}</span>@endif</div>
                            @else
                                <p class="small text-muted">{{ __('study.no_target') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="border-top mt-3 pt-3"><strong>{{ __('study.overall') }}: {{ $report['current']['rates']['overall'] !== null ? $report['current']['rates']['overall'].'%' : __('study.no_target') }}</strong>@if($report['current']['rates']['overall'] !== null)<span class="small text-muted ms-2">{{ __('study.state_'.$report['current']['state']) }}</span>@endif</div>
            </div>
        </section>
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}" @unless($panel) x-show="studySection === 'history'" x-cloak @endunless>
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.four_weeks') }}</h2><p>{{ __('study.trend_help') }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3 row g-4' : 'member-form-body row g-4' }}">
                @foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'] as $field => $label)
                    @php($scale = max(1, collect($report['trend'])->max(fn ($row) => max($row['actual'][$field], $row['target'][$field] ?? 0))))
                    <div class="col-lg-4">
                        <h3 class="h6">{{ __('study.'.$label) }}</h3>
                        @if($panel)
                        @foreach($report['trend'] as $row)
                            <div class="mb-3">
                                <div class="small mb-1">{{ $panel ? \App\Helpers\StudyCalendar::day($row['start'])->format('d/m') : \App\Helpers\StudyCalendar::label($row['start'], 'j M') }} — {{ $panel ? \App\Helpers\StudyCalendar::day($row['end'])->format('d/m') : \App\Helpers\StudyCalendar::label($row['end'], 'j M Y') }}</div>
                                <div class="small">{{ __('study.goal') }}: {{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($row['target'][$field] ?? null) : ($row['target'][$field] ?? '—') }}</div>
                                <div class="progress" role="progressbar" aria-label="{{ __('study.goal') }}" aria-valuemin="0" aria-valuemax="{{ $scale }}" aria-valuenow="{{ $row['target'][$field] ?? 0 }}"><div class="progress-bar bg-secondary" style="width:{{ round(100 * ($row['target'][$field] ?? 0) / $scale, 2) }}%"></div></div>
                                <div class="small">{{ __('study.actual') }}: {{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($row['actual'][$field]) : $row['actual'][$field] }}</div>
                                <div class="progress" role="progressbar" aria-label="{{ __('study.actual') }}" aria-valuemin="0" aria-valuemax="{{ $scale }}" aria-valuenow="{{ $row['actual'][$field] }}"><div class="progress-bar" style="width:{{ round(100 * $row['actual'][$field] / $scale, 2) }}%; background-color:#34317d"></div></div>
                            </div>
                        @endforeach
                        @else
                            <div class="study-chart-legend"><span><i class="study-legend-goal" aria-hidden="true"></i>{{ __('study.goal') }}</span><span><i class="study-legend-actual" aria-hidden="true"></i>{{ __('study.actual') }}</span></div>
                            <div class="study-trend-chart" aria-label="{{ __('study.four_weeks') }} · {{ __('study.'.$label) }}">
                                @foreach($report['trend'] as $row)
                                    <figure class="study-trend-week">
                                        <div class="study-trend-columns">
                                            @foreach(['target' => 'goal', 'actual' => 'actual'] as $source => $series)
                                                @php($quantity = $row[$source][$field] ?? null)
                                                <div class="study-trend-column">
                                                    <span class="study-trend-bar study-series-{{ $series }}" style="height:{{ round(100 * ($quantity ?? 0) / $scale, 2) }}%" title="{{ __('study.'.$series) }}: {{ $field === 'minutes' && $quantity !== null ? \App\Helpers\StudyInput::hours($quantity) : ($quantity ?? '—') }}">
                                                        <span>{{ $field === 'minutes' && $quantity !== null ? \App\Helpers\StudyInput::hours($quantity) : ($quantity ?? '—') }}</span>
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <figcaption>{{ \App\Helpers\StudyCalendar::label($row['start'], 'j M') }}<span>{{ \App\Helpers\StudyCalendar::label($row['end'], 'j M') }}</span></figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}" @unless($panel) x-show="studySection === 'history'" x-cloak @endunless>
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __($panel ? 'study.period_history_panel' : 'study.history_title') }}</h2><p>{{ __($panel ? 'study.period_help' : 'study.history_help') }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <label id="study-history-content" for="study-season" class="form-label">{{ __($panel ? 'study.period' : 'study.period_with_dates') }}</label>
                <select id="study-season" class="form-select mb-3" wire:model.live="seasonId">
                    @if($seasons->isEmpty())<option value="">{{ __('study.no_period') }}</option>@endif
                    @foreach($seasons as $season)<option value="{{ $season->id }}">{{ $season->name }}@unless($panel) · {{ \App\Helpers\StudyCalendar::label($season->start_date, 'j F Y') }} — {{ \App\Helpers\StudyCalendar::label($season->end_date, 'j F Y') }}@endunless</option>@endforeach
                </select>
                <x-badge.error field="seasonId" />
                <div class="table-responsive">
                    <table id="study-history-table" class="table align-middle">
                        <thead><tr><th scope="col">{{ __('study.week') }}</th><th scope="col">{{ __('study.questions') }}<br>{{ __('study.goal_actual') }}</th><th scope="col">{{ __('study.pages') }}<br>{{ __('study.goal_actual') }}</th><th scope="col">{{ __('study.hours') }}<br>{{ __('study.goal_actual') }}</th><th scope="col">{{ __('study.overall') }}</th><th scope="col">{{ __('study.state') }}</th></tr></thead>
                        <tbody>
                            @forelse($history as $row)
                                <tr><th scope="row">@if($row['start'] <= \App\Helpers\StudyCalendar::bounds(now())['start']->toDateString())<a href="{{ route($panel ? 'admin.study.show' : 'frontend.study', ['week' => $row['start'], 'season' => $seasonId] + ($panel ? ['user' => $studentId] : [])) }}">{{ $panel ? $row['start'] : \App\Helpers\StudyCalendar::label($row['start'], 'j M') }} — {{ $panel ? $row['end'] : \App\Helpers\StudyCalendar::label($row['end'], 'j M Y') }}</a>@else{{ $panel ? $row['start'] : \App\Helpers\StudyCalendar::label($row['start'], 'j M') }} — {{ $panel ? $row['end'] : \App\Helpers\StudyCalendar::label($row['end'], 'j M Y') }}@endif</th><td>{{ $row['target']['questions'] ?? '—' }} / {{ $row['actual']['questions'] }}</td><td>{{ $row['target']['pages'] ?? '—' }} / {{ $row['actual']['pages'] }}</td><td>{{ \App\Helpers\StudyInput::hours($row['target']['minutes'] ?? null) ?: '—' }} / {{ \App\Helpers\StudyInput::hours($row['actual']['minutes']) }}</td><td>{{ $row['rates']['overall'] !== null ? $row['rates']['overall'].'%' : '—' }}</td><td><span @class(['study-state' => ! $panel])>{{ __('study.state_'.$row['state']) }}</span></td></tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">{{ __('study.no_period') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $panel ? $history->links() : $history->links(data: ['scrollTo' => '#study-history-table']) }}
            </div>
        </section>
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}" @unless($panel) x-show="studySection === 'analysis'" x-cloak @endunless>
            <header id="study-analysis-content" class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.topic_analysis') }}</h2><p>{{ __($panel ? 'study.topic_help' : 'study.analysis_simple', ['minimum' => \App\Helpers\StudyTrackingSettings::minimumTopicQuestions()]) }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <div class="table-responsive">
                <table id="study-topic-table" class="table align-middle">
                    <thead><tr><th scope="col">{{ __('study.topic') }}</th><th scope="col">{{ __('study.correct') }}</th><th scope="col">{{ __('study.incorrect') }}</th><th scope="col">{{ __('study.accuracy') }}</th><th scope="col">{{ __('study.state') }}</th></tr></thead>
                    <tbody>
                        @forelse($topics as $topic)
                            <tr><th scope="row">{{ $topic['name'] }}<div class="small text-muted">{{ $topic['lesson'] }}</div></th><td>{{ $topic['correct'] }}</td><td>{{ $topic['incorrect'] }}</td><td>{{ $topic['rate'] }}%</td><td>{{ __('study.'.$topic['label']) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">{{ __('study.no_analysis') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                {{ $panel ? $topics->links() : $topics->links(data: ['scrollTo' => '#study-topic-table']) }}
            </div>
        </section>
        @if($report['comparisons'])
            <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}" @unless($panel) x-show="studySection === 'analysis'" x-cloak @endunless>
                <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.comparison') }}</h2><p>{{ __('study.comparison_help') }}</p></div></header>
                <div class="{{ $panel ? 'block-content pb-3 row g-4' : 'member-form-body row g-4' }}">
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
        <div wire:loading role="status" class="small text-muted mb-3">{{ __('study.processing') }}</div>
    @endif
</div>
