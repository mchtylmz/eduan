<div>
    @if($activeSeason)
        <h5 class="p-3 bg-light mb-3">
            {{ __('Dönem') }} : {{ dateFormat($activeSeason->start_date, 'd M Y') }} - {{ dateFormat($activeSeason->end_date, 'd M Y') }}
        </h5>
    @endif

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center my-2 mb-4">
        @foreach($tabs as $tabIndex => $tabTitle)
            <button type="button" class="btn {{ $activeTab == $tabIndex ? 'btn-secondary' : 'btn-outline-secondary' }}"
                    wire:click="chooseTab('{{ $tabIndex }}')"
                    wire:loading.attr="disabled">
                @if($activeTab == $tabIndex)
                    <i class="fa fa-check mx-1"></i>
                @endif
                {{ $tabTitle }}
            </button>
        @endforeach
    </div>

    <div class="w-100 my-3" wire:loading>
        <div class="alert alert-warning p-3 py-2 fw-bold w-100">
            <i class="fa fa-spinner fa-pulse mx-1"></i> {{ __('Yükleniyor') }}...
        </div>
    </div>

    <div class="table-responsive">
        <h5 class="p-3 bg-light my-2">{{ $tabs[$activeTab] }}</h5>

        @if(count($results = $this->results()))
        <table class="table table-responsive table-striped table-bordered w-100">
            <thead>
            <tr>
                @if($activeTab == 'questions')
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Tarih') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
                @endif
                @if(in_array($activeTab, ['lessonsDesc', 'lessonsAsc', 'topicsDesc', 'topicsAsc']))
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Ders') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Boş Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
                @endif
            </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    @if($activeTab == 'questions')
                        @php($rate = round($result->correct_count * 100.0 / $result->question_count, 2))
                        <tr>
                            <td class="text-center" scope="col">{{ dateFormat($result->updated_at) }}</td>
                            <td class="text-center" scope="col">{{ $result->question_count }}</td>
                            <td class="text-center" scope="col">{{ $result->correct_count }}</td>
                            <td class="text-center" scope="col">{{ $result->incorrect_count }}</td>
                            <td class="text-center" scope="col">
                                <strong class="{{ $rate >= 50 ? 'text-success' : 'text-dark' }}">%{{ $rate }}</strong>
                            </td>
                        </tr>
                    @endif
                    @if(in_array($activeTab, ['lessonsDesc', 'lessonsAsc', 'topicsDesc', 'topicsAsc']))
                        @php($rate = round($result->correct_count * 100.0 / $result->total_count, 2))
                        <tr>
                            <td class="text-center" scope="col">{{ getJsonLocaleValue($result->name) }}</td>
                            <td class="text-center" scope="col">{{ $result->total_count }}</td>
                            <td class="text-center" scope="col">{{ $result->correct_count }}</td>
                            <td class="text-center" scope="col">
                                {{ $result->total_count - ($result->empty_count + $result->correct_count) }}
                            </td>
                            <td class="text-center" scope="col">{{ $result->empty_count }}</td>
                            <td class="text-center" scope="col">
                                <strong class="{{ $rate >= 25 ? 'text-success' : 'text-dark' }}">%{{ $rate }}</strong>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div>{{ $results->links() }}</div>
        @else
            <div class="alert alert-danger fw-bold">
                {{ __('Gösterilecek sonuç bulunmuyor!') }}
            </div>
        @endif
    </div>
</div>
