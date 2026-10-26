@php
    $evaluationDays = collect($report['days'])->keyBy('activity_date');
    $evaluationFields = ['questions' => 'questions', 'pages' => 'pages', 'minutes' => 'hours'];
    $evaluationStart = \App\Helpers\StudyCalendar::day($report['current']['start']);
    $evaluationToday = \App\Helpers\StudyCalendar::day(now())->toDateString();
@endphp
<section id="study-evaluation-content" class="member-form-card mb-4" x-show="studySection === 'evaluation'" x-cloak>
    <header class="member-form-heading"><div><h2 id="study-evaluation-title">{{ __('study.evaluation_title') }}</h2><p>{{ __('study.evaluation_help') }}</p></div></header>
    <div class="member-form-body">
        <figure class="study-matrix" aria-labelledby="study-evaluation-title">
            <div class="study-matrix-legend" aria-hidden="true"><span>{{ __('study.less_work') }}</span><i></i><i></i><i></i><span>{{ __('study.more_work') }}</span></div>
            <table class="study-matrix-table">
                <caption class="visually-hidden">{{ __('study.evaluation_title') }} · {{ \App\Helpers\StudyCalendar::label($evaluationStart) }} — {{ \App\Helpers\StudyCalendar::label($evaluationStart->addDays(6)) }}</caption>
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
        <div class="study-evaluation-overall"><span>{{ __('study.overall') }}</span><strong>{{ $report['current']['rates']['overall'] !== null ? $report['current']['rates']['overall'].'%' : __('study.no_target') }}</strong></div>
    </div>
</section>
