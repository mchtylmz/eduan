<div class="card border">
    <div class="card-header text-dark bg-light border-0 py-3 d-flex align-items-center gap-2">
        <img alt="{{ $name }}" src="{{ asset('uploads/podium.png') }}" />
        <h5 class="card-title mb-0 mx-auto">{{ $name }}</h5>
    </div>
    <div class="card-body p-0 border-0">
        <table class="table mb-0">
            <thead>
            <tr>
                <th class="text-center" scope="col"></th>
                <th scope="col">{{ __('İsim') }} {{ __('Soyisim') }}</th>
                <th class="text-center" scope="col">{{ __('Puan') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($this->results as $result)
                <tr class="bg-league{{ $ranking }}-{{ $result->position }} {{ $result->user_id == auth()->id() ? 'my-league-position': '' }}">
                    <th class="text-center" scope="row">{{ $result->position }}</th>
                    <td>{{ $result->user->name }} {{ $result->user->surname }}</td>
                    <td class="text-{{ $result->position > 3 ? 'center' : 'end' }}">
                        {{ $result->total_score }}
                        @switch($result->position)
                            @case(1)
                                <i class="fa fa-trophy-star fa-1_75x mx-2" style="color:#FFD700"></i>
                                @break
                            @case(2)
                                <i class="fa fa-trophy-star fa-1_70x mx-2" style="color:#C0C0C0"></i>
                                @break
                            @case(3)
                                <i class="fa fa-trophy fa-1_65x mx-2" style="color:#CD7F32"></i>
                                @break
                        @endswitch
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
