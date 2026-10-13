<div class="member-statistics">
    <div class="member-report-filter">
        <div>
            <label for="member-statistics-report">{{ __('account.report') }}</label>
            <select id="member-statistics-report" class="form-select" wire:model.live="activeTab" wire:loading.attr="disabled">
                @foreach($tabs as $tabIndex => $tabTitle)
                    <option value="{{ $tabIndex }}">{{ $tabTitle }}</option>
                @endforeach
            </select>
        </div>
        @if($activeSeason)
            <span class="member-period"><i class="fa-light fa-calendar" aria-hidden="true"></i>{{ __('account.period') }}: {{ dateFormat($activeSeason->start_date, 'd M Y') }} – {{ dateFormat($activeSeason->end_date, 'd M Y') }}</span>
        @endif
    </div>
    <p class="member-loading" wire:loading role="status"><i class="fa fa-spinner fa-pulse" aria-hidden="true"></i> {{ __('account.loading') }}…</p>
    <h2 class="member-report-title">{{ $tabs[$activeTab] }}</h2>
    @php
        $results = $this->results();
    @endphp
    <div class="member-stat-list">
        @forelse($results as $result)
            @php
                $daily = $activeTab === 'questions';
                $total = $daily ? $result->question_count : $result->total_count;
                $incorrect = $daily ? $result->incorrect_count : $total - ($result->empty_count + $result->correct_count);
                $rate = $total > 0 ? round($result->correct_count * 100.0 / $total, 2) : 0;
                $recordTitle = $daily ? dateFormat($result->updated_at, 'd M Y') : getJsonLocaleValue($result->name);
            @endphp
            <article class="member-stat-card">
                <header>
                    <div><span class="member-record-label">{{ __($daily ? 'account.date' : (str_starts_with($activeTab, 'topics') ? 'account.topic' : 'account.lesson')) }}</span><h3>{{ $recordTitle }}</h3></div>
                    <div class="member-success"><span>{{ __('account.success') }}</span><strong>{{ number_format($rate, 2, ',', '.') }}%</strong></div>
                </header>
                <progress class="member-success-progress" max="100" value="{{ $rate }}" aria-label="{{ __('account.success') }}"></progress>
                <dl class="member-record-metrics">
                    <div><dt>{{ __('account.questions_count') }}</dt><dd>{{ $total }}</dd></div>
                    <div><dt>{{ __('account.correct') }}</dt><dd class="member-correct">{{ $result->correct_count }}</dd></div>
                    <div><dt>{{ __('account.incorrect') }}</dt><dd class="member-incorrect">{{ $incorrect }}</dd></div>
                    @unless($daily)<div><dt>{{ __('account.unanswered') }}</dt><dd>{{ $result->empty_count }}</dd></div>@endunless
                </dl>
            </article>
        @empty
            <p class="member-empty">{{ __('account.empty') }}</p>
        @endforelse
    </div>
    {{ $results->links('components.frontend.livewire-pagination', ['scrollTo' => '.member-content']) }}
</div>
