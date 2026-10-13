<div class="league-results-page">
    <div class="league-period-header">
        <div class="league-period-summary">
            <span class="league-section-label"><i class="fa fa-trophy" aria-hidden="true"></i> {{ __('Lig Sonuçları') }}</span>
            <h2 class="league-period-title">
                @if($selectedLeague)
                    <span>{{ dateFormat($selectedLeague->start_at, 'd M Y') }}</span>
                    <span class="league-date-divider" aria-hidden="true">—</span>
                    <span>{{ dateFormat($selectedLeague->end_at, 'd M Y') }}</span>
                @else
                    {{ __('Geçmiş Lig Sonuçları') }}
                @endif
            </h2>
        </div>
        <div class="dropdown league-history" x-data="{
            search: '',
            matches(value) {
                const normalize = text => text.toLocaleLowerCase(document.documentElement.lang || 'nl').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                return normalize(value).includes(normalize(this.search.trim()));
            }
        }">
            <button id="league-history-toggle" type="button" class="league-history-toggle dropdown-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <span><i class="fa fa-calendar-alt me-2" aria-hidden="true"></i>{{ __('Geçmiş Lig Sonuçları') }}</span>
                <i class="fa fa-spinner fa-pulse choose-league-loading d-none" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu league-history-menu" aria-labelledby="league-history-toggle">
                <div class="league-history-search">
                    <label for="league-history-search" class="form-label">{{ __('leagues.search_label') }}</label>
                    <div class="league-search-control">
                        <i class="fa fa-search" aria-hidden="true"></i>
                        <input id="league-history-search" type="search" class="form-control" x-model="search"
                               placeholder="{{ __('leagues.search_placeholder') }}" autocomplete="off">
                    </div>
                </div>
                <ul class="league-history-options" x-ref="periods">
                    @foreach($this->leagues() as $league)
                        <li data-search="{{ dateFormat($league->start_at, 'd M Y').' '.dateFormat($league->end_at, 'd M Y').' '.$league->start_at?->format('d.m.Y Y-m-d').' '.$league->end_at?->format('d.m.Y Y-m-d') }}"
                            x-show="matches($el.dataset.search)">
                            <a @class(['dropdown-item choose-league', 'is-selected' => $selectedLeague?->id === $league->id])
                               href="{{ route('frontend.leagues.detail', $league->code) }}"
                               @if($selectedLeague?->id === $league->id) aria-current="page" @endif>
                                <span>{{ dateFormat($league->start_at, 'd M Y') }} — {{ dateFormat($league->end_at, 'd M Y') }}</span>
                                @if($selectedLeague?->id === $league->id)
                                    <i class="fa fa-check" aria-hidden="true"></i>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="league-history-empty" role="status" x-cloak
                   x-show="!Array.from($refs.periods.children).some(item => matches(item.dataset.search))">
                    {{ __('Gösterilecek sonuç bulunmuyor!') }}
                </p>
            </div>
        </div>
    </div>

    @if($selectedLeague)
        <div class="row g-3 league-standings-grid">
            @foreach(range(1, 3) as $ranking)
                <div class="col-lg-4">
                    @livewire('frontend.leagues.league-result-table', [
                        'league' => $selectedLeague,
                        'name' => __($ranking.'. Lig'),
                        'ranking' => $ranking
                    ], key('league-'.$selectedLeague->id.'-'.$ranking))
                </div>
            @endforeach
        </div>
    @else
        <div class="league-page-empty" role="status">
            <i class="fa fa-trophy" aria-hidden="true"></i>
            <p>{{ __('Gösterilecek sonuç bulunmuyor!') }}</p>
        </div>
    @endif
</div>
