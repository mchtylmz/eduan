@php
    $evaluationDays = collect($report['days'])->keyBy('activity_date');
    $evaluationFields = ['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'];
    $evaluationStart = \App\Helpers\StudyCalendar::day($report['current']['start']);
    $evaluationToday = \App\Helpers\StudyCalendar::day(now())->toDateString();
    $evaluationTitle = $evaluationTitle ?? __('study.evaluation_title');
    $evaluationChartTitle = $evaluationChartTitle ?? __('study.evaluation_chart_title');
    $evaluationHelp = $evaluationHelp ?? __('study.evaluation_help');
@endphp
<section id="study-evaluation-content" class="member-form-card mb-4" x-show="studySection === 'evaluation'" x-cloak>
    <header class="member-form-heading"><div><h2 id="study-evaluation-title">{{ $evaluationTitle }}</h2><p>{{ $evaluationHelp }}</p></div></header>
    <div class="member-form-body">
        <div class="study-evaluation-layout">
            <section class="study-evaluation-visual" aria-labelledby="study-evaluation-chart-title">
                <h3 id="study-evaluation-chart-title">{{ $evaluationChartTitle }}</h3>
                <div class="study-evaluation-rings">
                    <svg viewBox="0 0 280 280" role="img" aria-labelledby="study-rings-title study-rings-description">
                        <title id="study-rings-title">{{ $evaluationChartTitle }}</title>
                        <desc id="study-rings-description">{{ __('study.evaluation_ring_help') }}</desc>
                        @foreach($evaluationFields as $field => $label)
                            @php
                                $rate = $report['current']['rates'][$field];
                            @endphp
                            <g class="study-metric-{{ $field }}" data-ring="{{ $field }}" data-rate="{{ $rate ?? '' }}">
                                <title>{{ __('study.'.$label) }}: {{ $rate !== null ? $rate.'%' : __('study.no_target') }}</title>
                                <circle class="study-ring-track" cx="140" cy="140" r="{{ 108 - 23 * $loop->index }}" />
                                @if($rate !== null && $rate > 0)
                                    <circle class="study-ring-amount" cx="140" cy="140" r="{{ 108 - 23 * $loop->index }}" pathLength="100" stroke-dasharray="{{ min(100, $rate) }} 100" transform="rotate(-90 140 140)" />
                                @endif
                            </g>
                        @endforeach
                    </svg>
                    <div class="study-evaluation-overall study-ring-score"><span>{{ __('study.overall') }}</span><strong>{{ $report['current']['rates']['overall'] !== null ? $report['current']['rates']['overall'].'%' : '—' }}</strong></div>
                </div>
                <dl class="study-ring-legend">
                    @foreach($evaluationFields as $field => $label)
                        @php
                            $rate = $report['current']['rates'][$field];
                            $goal = $report['current']['target'][$field] ?? null;
                            $actual = $report['current']['actual'][$field];
                        @endphp
                        <div class="study-metric-{{ $field }}">
                            <dt><i aria-hidden="true"></i>{{ __('study.'.$label) }}<strong>{{ $rate !== null ? $rate.'%' : '—' }}</strong></dt>
                            <dd><span>{{ __('study.actual') }}: <b>{{ $field === 'minutes' ? \App\Helpers\StudyInput::hours($actual) : $actual }}</b></span><span>{{ __('study.goal') }}: {{ $goal === null ? '—' : ($field === 'minutes' ? \App\Helpers\StudyInput::hours($goal) : $goal) }}</span>@if($rate === null)<span>{{ __('study.no_target') }}</span>@endif</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="study-ring-help">{{ __('study.evaluation_ring_help') }}</p>
            </section>
            <figure class="study-matrix" aria-labelledby="study-evaluation-title">
                <div class="study-matrix-legend" aria-hidden="true"><span>{{ __('study.less_work') }}</span><i></i><i></i><i></i><span>{{ __('study.more_work') }}</span></div>
                <table class="study-matrix-table">
                    <caption class="visually-hidden">{{ $evaluationTitle }} · {{ \App\Helpers\StudyCalendar::label($evaluationStart) }} — {{ \App\Helpers\StudyCalendar::label($evaluationStart->addDays(6)) }}</caption>
                    <thead><tr><th scope="col">{{ __('study.date') }}</th>@foreach($evaluationFields as $label)<th scope="col">{{ __('study.'.$label) }}</th>@endforeach</tr></thead>
                    <tbody>
                        @for($offset = 0; $offset < 7; $offset++)
                            @php
                                $day = $evaluationStart->addDays($offset);
                                $date = $day->toDateString();
                                $amounts = $evaluationDays->get($date);
                                $future = $amounts === null && $date > $evaluationToday;
                            @endphp
                            <tr data-activity-date="{{ $date }}">
                                <th scope="row"><time datetime="{{ $date }}">{{ \App\Helpers\StudyCalendar::label($day, 'j M') }}<small>{{ \App\Helpers\StudyCalendar::label($day, 'D') }}</small></time></th>
                                @foreach($evaluationFields as $field => $label)
                                    @php
                                        $value = $amounts[$field] ?? 0;
                                        $peak = max(1, $evaluationDays->max($field) ?? 0);
                                        $intensity = $value > 0 ? round(0.15 + 0.65 * $value / $peak, 3) : 0.04;
                                        $display = $future ? '—' : ($field === 'minutes' ? \App\Helpers\StudyInput::hours($value) : $value);
                                    @endphp
                                    <td data-field="{{ $field }}"><span @class(['study-matrix-cell', 'is-strong' => $intensity >= 0.6, 'is-future' => $future]) style="--intensity:{{ $intensity }}" title="{{ $future ? __('study.upcoming_day') : __('study.'.$label).': '.$display }}"><span>{{ $display }}</span></span></td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        @foreach(['target' => 'goal', 'actual' => 'actual', 'rates' => 'completion'] as $source => $label)
                            <tr data-series="{{ $source }}"><th scope="row">{{ __('study.'.$label) }}</th>
                                @foreach($evaluationFields as $field => $fieldLabel)
                                    @php($value = $report['current'][$source][$field] ?? null)
                                    <td>{{ $value === null ? '—' : ($source === 'rates' ? $value.'%' : ($field === 'minutes' ? \App\Helpers\StudyInput::hours($value) : $value)) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tfoot>
                </table>
                <figcaption>{{ __('study.evaluation_sources') }}</figcaption>
            </figure>
        </div>
    </div>
</section>
