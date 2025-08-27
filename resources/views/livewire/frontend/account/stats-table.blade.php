<div>
    <div class="table-responsive">
        @if($tab == 'tests')
            <h5 class="p-3 bg-light">{{ __('Çözülen Testlerde Konu Başarı Tablosu') }}</h5>
        @endif
        @if($tab == 'exams')
            <h5 class="p-3 bg-light">{{ __('Çözülen Sınavlarda Konu Başarı Tablosu') }}</h5>
        @endif
        <table class="table table-responsive table-striped table-bordered w-100">
            <thead>
            <tr>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Ders Adı') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Konu Adı') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
            </tr>
            </thead>
            <tbody>
            @if(count($results = $this->statsDetailData()))
                @foreach($results as $result)
                    <tr>
                        <td class="text-start" scope="col">
                            @if(str($result->lesson_title)->isJson())
                                {{ json_decode($result->lesson_title, true)[app()->getLocale()] ?? '-' }}
                            @else
                                {{ $result->lesson_title }}
                            @endif
                        </td>
                        <td class="text-start" scope="col">
                            @if(str($result->topic_title)->isJson())
                                {{ json_decode($result->topic_title, true)[app()->getLocale()] ?? '-' }}
                            @else
                                {{ $result->topic_title }}
                            @endif
                        </td>
                        <td class="text-center" scope="col">{{ $result->total_questions }}</td>
                        <td class="text-center" scope="col">{{ $result->count_correct }}</td>
                        <td class="text-center" scope="col">{{ $result->count_incorrect }}</td>
                        <td class="text-center" scope="col">
                            %{{ round($result->count_correct * 100.0 / $result->total_questions, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" scope="col">{{ __('Gösterilecek sonuç bulunmuyor!') }}</td>
                </tr>
            @endif

            </tbody>
        </table>

        <div>{{ $results->links() }}</div>
    </div>
</div>
