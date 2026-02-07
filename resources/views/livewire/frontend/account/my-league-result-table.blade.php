<div>
    <div class="table-responsive my-3">
        <table class="table table-responsive table-striped table-bordered w-100">
            <thead>
            <tr>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Başlangıç Tarihi') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Bitiş Tarihi') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Lig') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Puan') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Sıra') }}</th>
            </tr>
            </thead>
            <tbody>
            @if(count($results = $this->results()))
                @foreach($results as $result)
                    <tr>
                        <td class="text-center" scope="col">{{ dateFormat($result->league->start_at) }}</td>
                        <td class="text-center" scope="col">{{ dateFormat($result->league->end_at) }}</td>
                        <td class="text-center" scope="col">{{ $leagueNames[$result->league_number] ?? '-' }}</td>
                        <td class="text-center" scope="col">{{ $result->total_correct + $result->total_incorrect }}</td>
                        <td class="text-center" scope="col">{{ $result->total_correct }}</td>
                        <td class="text-center" scope="col">{{ $result->total_incorrect }}</td>
                        <td class="text-center" scope="col">{{ $result->total_score }}</td>
                        <td class="text-center" scope="col">{{ $result->position }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" scope="col">{{ __('Gösterilecek sonuç bulunmuyor!') }}</td>
                </tr>
            @endif

            </tbody>
        </table>

    </div>
</div>
