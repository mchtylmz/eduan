<div class="member-league-results">
    @php($results = $this->results())
    <div class="member-league-grid">
        @forelse($results as $result)
            <article class="member-league-card" wire:key="member-league-{{ $result->id }}">
                <header>
                    <span class="member-league-badge"><i class="fa-light fa-trophy" aria-hidden="true"></i>{{ __('account.league_number', ['number' => $result->league_number]) }}</span>
                    <span class="member-position">{{ __('account.position') }} <strong>{{ $result->position }}</strong></span>
                </header>
                <div class="member-league-period">
                    <span class="member-record-label">{{ __('account.period') }}</span>
                    <h2>@if($result->league){{ dateFormat($result->league->start_at, 'd M Y') }} – {{ dateFormat($result->league->end_at, 'd M Y') }}@else{{ __('account.unavailable_period') }}@endif</h2>
                </div>
                <div class="member-league-score"><strong>{{ $result->total_score }}</strong><span>{{ __('account.points') }}</span></div>
                <dl class="member-record-metrics">
                    <div><dt>{{ __('account.questions_count') }}</dt><dd>{{ $result->total_correct + $result->total_incorrect }}</dd></div>
                    <div><dt>{{ __('account.correct') }}</dt><dd class="member-correct">{{ $result->total_correct }}</dd></div>
                    <div><dt>{{ __('account.incorrect') }}</dt><dd class="member-incorrect">{{ $result->total_incorrect }}</dd></div>
                </dl>
            </article>
        @empty
            <p class="member-empty">{{ __('account.empty') }}</p>
        @endforelse
    </div>
    {{ $results->links('components.frontend.livewire-pagination', ['scrollTo' => '.member-content']) }}
</div>
