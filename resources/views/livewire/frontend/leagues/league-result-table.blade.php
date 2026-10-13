<div class="league-standings-card">
    @php($standings = $this->results)
    <div class="league-standings-header">
        <div class="league-standings-title">
            <span class="league-section-label">{{ __('Lig Sonuçları') }}</span>
            <h2>{{ $name }}</h2>
        </div>
        @if($standings->isNotEmpty())
            <div class="league-mini-podium">
                @foreach([2, 1, 3] as $position)
                    @php($winner = $standings->firstWhere('position', $position))
                    <div @class(['league-podium-place', 'league-podium-place-'.$position, 'is-empty' => !$winner])>
                        @if($winner)
                            <span class="league-podium-avatar" title="{{ trim($winner->user->name.' '.$winner->user->surname) }}">
                                <span aria-hidden="true">{{ mb_strtoupper(mb_substr($winner->user->name, 0, 1).mb_substr($winner->user->surname, 0, 1)) }}</span>
                                <span class="visually-hidden">{{ $position }}. {{ trim($winner->user->name.' '.$winner->user->surname) }}</span>
                            </span>
                        @else
                            <span class="league-podium-avatar" aria-hidden="true">—</span>
                        @endif
                        <span class="league-podium-step" aria-hidden="true">{{ $position }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    <table class="table league-standings-table mb-0">
        <caption class="visually-hidden">{{ $name }} — {{ __('Lig Sonuçları') }}</caption>
        <thead>
            <tr>
                <th class="league-position-column" scope="col"><span class="visually-hidden">{{ __('leagues.position') }}</span>#</th>
                <th scope="col">{{ __('İsim') }} {{ __('Soyisim') }}</th>
                <th class="league-score-column" scope="col">{{ __('Puan') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($standings as $result)
                <tr @class(['bg-league'.$ranking.'-'.$result->position, 'my-league-position' => $result->user_id == auth()->id()])>
                    <th scope="row" class="league-position-column">
                        <span @class(['league-position', 'league-medal-'.$result->position => $result->position <= 3])>{{ $result->position }}</span>
                    </th>
                    <td class="league-person-name">{{ $result->user->name }} {{ $result->user->surname }}</td>
                    <td class="league-score-column">
                        <span class="league-score">{{ $result->total_score }}</span>
                        @if($result->position <= 3)
                            <i class="fa fa-trophy league-trophy league-medal-{{ $result->position }}" aria-hidden="true"></i>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="league-standing-empty">{{ __('Gösterilecek sonuç bulunmuyor!') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
