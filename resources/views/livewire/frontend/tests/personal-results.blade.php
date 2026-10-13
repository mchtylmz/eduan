<div class="practice-personal-results">
    @auth
        @php
            $totals = $this->totals();
            $attempts = $this->attempts();
        @endphp
        @if($totals['results_count'])
            <div class="practice-results-summary">
                <h2>{{ __('learning.all_attempts') }}</h2>
                <dl class="practice-result-metrics">
                    <div><dt>{{ __('learning.attempt') }}</dt><dd>{{ $totals['results_count'] }}</dd></div>
                    <div><dt>{{ __('account.questions_count') }}</dt><dd>{{ $totals['question_count'] }}</dd></div>
                    <div><dt>{{ __('account.correct') }}</dt><dd class="text-success">{{ $totals['correct_count'] }}</dd></div>
                    <div><dt>{{ __('account.incorrect') }}</dt><dd class="text-danger">{{ $totals['incorrect_count'] }}</dd></div>
                </dl>
            </div>
            <div class="practice-attempt-list">
                @foreach($attempts as $attempt)
                    <article class="practice-attempt" wire:key="practice-attempt-{{ $attempt->id }}">
                        <header>
                            <h3>{{ __('learning.attempt') }} {{ $attempts->total() - $attempts->firstItem() - $loop->index + 1 }}</h3>
                            <span>{{ dateFormat($attempt->created_at, 'd M Y, H:i') }}</span>
                            <span class="practice-attempt-status">{{ \App\Enums\YesNoEnum::YES->is($attempt->completed) ? __('learning.completed') : __('learning.in_progress') }}</span>
                        </header>
                        <dl class="practice-result-metrics">
                            <div><dt>{{ __('account.questions_count') }}</dt><dd>{{ $attempt->answered_count }}</dd></div>
                            <div><dt>{{ __('account.correct') }}</dt><dd class="text-success">{{ $attempt->correct_count }}</dd></div>
                            <div><dt>{{ __('account.incorrect') }}</dt><dd class="text-danger">{{ $attempt->incorrect_count }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
            {{ $attempts->links('components.frontend.livewire-pagination', ['scrollTo' => '#pills-results']) }}
            <a href="{{ route('frontend.test.solutions', $exam->code) }}" class="practice-result-link">{{ __('learning.view_results') }} <i class="fa-light fa-arrow-right" aria-hidden="true"></i></a>
        @else
            <p class="member-empty">{{ __('learning.no_results') }}</p>
        @endif
    @else
        <div class="member-empty"><p>{{ __('learning.login_results') }}</p><a href="{{ route('login') }}" class="theme-btn">{{ __('Giriş Yap') }}</a></div>
    @endauth
</div>
