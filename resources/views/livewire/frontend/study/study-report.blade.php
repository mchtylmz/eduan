@php($panel = $this instanceof \App\Livewire\Backend\Study\StudentReport)
<div class="mb-4">
    @if($report)
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}">
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __($panel ? 'study.weekly_report_panel' : 'study.weekly_report') }}</h2><p>{{ __('study.rates_help') }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <div class="row g-4">
                    @foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'] as $field => $label)
                        @php($rate = $report['current']['rates'][$field])
                        <div class="col-md-4">
                            <h3 class="h6">{{ __('study.'.$label) }}</h3>
                            <div class="d-flex justify-content-between gap-2 mb-2"><strong>{{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($report['current']['actual'][$field]) : $report['current']['actual'][$field] }}</strong><span>{{ __('study.goal') }}: {{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($report['current']['target'][$field] ?? null) : ($report['current']['target'][$field] ?? '—') }}</span></div>
                            @if($rate !== null)
                                <div class="progress" role="progressbar" aria-label="{{ __('study.'.$label) }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, $rate) }}" aria-valuetext="{{ $rate }}%"><div class="progress-bar" style="width:{{ min(100, $rate) }}%; background-color:#34317d"></div></div>
                                <div class="small">{{ $rate }}% @if($rate > 100)<span>{{ __('study.exceeded') }}</span>@endif</div>
                            @else
                                <p class="small text-muted">{{ __('study.no_target') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="border-top mt-3 pt-3"><strong>{{ __('study.overall') }}: {{ $report['current']['rates']['overall'] !== null ? $report['current']['rates']['overall'].'%' : __('study.no_target') }}</strong><span class="small text-muted ms-2">{{ __('study.state_'.$report['current']['state']) }}</span></div>
            </div>
        </section>
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}">
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.four_weeks') }}</h2><p>{{ __('study.trend_help') }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3 row g-4' : 'member-form-body row g-4' }}">
                @foreach(['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'] as $field => $label)
                    @php($scale = max(1, collect($report['trend'])->max(fn ($row) => max($row['actual'][$field], $row['target'][$field] ?? 0))))
                    <div class="col-lg-4">
                        <h3 class="h6">{{ __('study.'.$label) }}</h3>
                        @foreach($report['trend'] as $row)
                            <div class="mb-3">
                                <div class="small mb-1">{{ \App\Helpers\StudyCalendar::day($row['start'])->format('d/m') }} — {{ \App\Helpers\StudyCalendar::day($row['end'])->format('d/m') }}</div>
                                <div class="small">{{ __('study.goal') }}: {{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($row['target'][$field] ?? null) : ($row['target'][$field] ?? '—') }}</div>
                                <div class="progress" role="progressbar" aria-label="{{ __('study.goal') }}" aria-valuemin="0" aria-valuemax="{{ $scale }}" aria-valuenow="{{ $row['target'][$field] ?? 0 }}"><div class="progress-bar bg-secondary" style="width:{{ round(100 * ($row['target'][$field] ?? 0) / $scale, 2) }}%"></div></div>
                                <div class="small">{{ __('study.actual') }}: {{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($row['actual'][$field]) : $row['actual'][$field] }}</div>
                                <div class="progress" role="progressbar" aria-label="{{ __('study.actual') }}" aria-valuemin="0" aria-valuemax="{{ $scale }}" aria-valuenow="{{ $row['actual'][$field] }}"><div class="progress-bar" style="width:{{ round(100 * $row['actual'][$field] / $scale, 2) }}%; background-color:#34317d"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}">
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __($panel ? 'study.period_history_panel' : 'study.period_history') }}</h2><p>{{ __('study.period_help') }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <label for="study-season" class="form-label">{{ __('study.period') }}</label>
                <select id="study-season" class="form-select mb-3" wire:model.live="seasonId">
                    @if($seasons->isEmpty())<option value="">{{ __('study.no_period') }}</option>@endif
                    @foreach($seasons as $season)<option value="{{ $season->id }}">{{ $season->name }}</option>@endforeach
                </select>
                <x-badge.error field="seasonId" />
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th scope="col">{{ __('study.week') }}</th><th scope="col">{{ __('study.questions') }}<br>{{ __('study.goal_actual') }}</th><th scope="col">{{ __('study.pages') }}<br>{{ __('study.goal_actual') }}</th><th scope="col">{{ __('study.hours') }}<br>{{ __('study.goal_actual') }}</th><th scope="col">{{ __('study.overall') }}</th><th scope="col">{{ __('study.state') }}</th></tr></thead>
                        <tbody>
                            @forelse($history as $row)
                                <tr><th scope="row">@if($row['start'] <= \App\Helpers\StudyCalendar::bounds(now())['start']->toDateString())<a href="{{ route($panel ? 'admin.study.show' : 'frontend.study', ['week' => $row['start'], 'season' => $seasonId] + ($panel ? ['user' => $studentId] : [])) }}">{{ $row['start'] }} — {{ $row['end'] }}</a>@else{{ $row['start'] }} — {{ $row['end'] }}@endif</th><td>{{ $row['target']['questions'] ?? '—' }} / {{ $row['actual']['questions'] }}</td><td>{{ $row['target']['pages'] ?? '—' }} / {{ $row['actual']['pages'] }}</td><td>{{ \App\Helpers\StudyInput::hours($row['target']['minutes'] ?? null) ?: '—' }} / {{ \App\Helpers\StudyInput::hours($row['actual']['minutes']) }}</td><td>{{ $row['rates']['overall'] !== null ? $row['rates']['overall'].'%' : '—' }}</td><td>{{ __('study.state_'.$row['state']) }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">{{ __('study.no_period') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $history->links() }}
            </div>
        </section>
        <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}">
            <header class="{{ $panel ? 'block-header block-header-default' : 'member-form-heading' }}"><div><h2 @class(['fs-5' => $panel])>{{ __('study.topic_analysis') }}</h2><p>{{ __('study.topic_help', ['minimum' => \App\Helpers\StudyTrackingSettings::minimumTopicQuestions()]) }}</p></div></header>
            <div class="{{ $panel ? 'block-content pb-3' : 'member-form-body' }}">
                <div class="table-responsive">
                <table class="table align-middle">
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
                {{ $topics->links() }}
            </div>
        </section>
        @if($report['comparisons'])
            <section class="{{ $panel ? 'block block-rounded border mb-4' : 'member-form-card mb-4' }}">
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
