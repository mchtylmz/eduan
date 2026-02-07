<div>
    <form method="post" wire:submit="filterResults">
        <div class="row align-items-end">
            <div class="col-lg-3 mb-3" wire:ignore>
                <label class="form-label" for="started_at">{{ __('Başlangıç Tarihi') }}</label>
                <input type="text" class="js-flatpickr form-control" id="started_at" wire:model="started_at" data-enable-time="false" data-time_24hr="false" data-locale="{{ $locale }}" value="{{ $started_at }}">
            </div>

            <div class="col-lg-3 mb-3" wire:ignore>
                <label class="form-label" for="ended_at">{{ __('Bitiş Tarihi') }}</label>
                <input type="text" class="js-flatpickr form-control" id="ended_at" wire:model="ended_at" data-enable-time="false" data-time_24hr="false" data-locale="{{ $locale }}" value="{{ $ended_at }}">
            </div>

            <div class="col-lg-3 mb-3" wire:ignore>
                <label class="form-label" for="selectedOrderBy">{{ __('Sıralama') }}</label>
                <select id="selectedOrderBy"
                        class="form-control selectpicker"
                        data-live-search="true"
                        data-size="10"
                        wire:model="selectedOrderBy">
                    @foreach($this->orderByList as $orderByRaw => $orderByDescription)
                        <option value="{{ $orderByRaw }}" @selected($orderByRaw == $selectedOrderBy)>
                            {{ $orderByDescription }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 mb-3">
                <button type="submit" class="btn btn-alt-primary px-4 mt-3" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-fw fa-filter me-1 opacity-50"></i> {{ __('Filtrele') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>
        </div>
    </form>

    @if($showResults)
        <div class="table-responsive mt-3">
            <div class="row align-items-end justify-content-between">
                <div class="col-lg-3">
                    <button type="button" class="btn btn-success px-4 mb-3" wire:click="export"
                            wire:loading.attr="disabled">
                        <div wire:loading.remove>
                            <i class="fa fa-fw fa-file-excel me-1"></i> {{ __('Excele Aktar') }}
                        </div>
                        <div wire:loading>
                            <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                        </div>
                    </button>
                </div>
                <div class="col-lg-3"></div>
            </div>

            <table class="table table-responsive table-striped table-bordered w-100">
                <thead>
                <tr>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Kullanıcı Adı') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('İsim') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Soyisim') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Toplam Soru') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Doğru Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Yanlış Yanıt') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Başarı Yüzdesi') }}</th>
                    <th scope="col" class="bg-secondary text-white text-center">{{ __('Son Güncelleme') }}</th>
                </tr>
                </thead>
                <tbody>
                @if(count($results = $this->filterResults()))
                    @foreach($results as $result)
                        <tr>
                            <td class="text-start" scope="col">{{ $result->username }}</td>
                            <td class="text-start" scope="col">{{ $result->name }}</td>
                            <td class="text-start" scope="col">{{ $result->surname }}</td>
                            <td class="text-center" scope="col">{{ $result->question_count }}</td>
                            <td class="text-center" scope="col">{{ $result->correct_count }}</td>
                            <td class="text-center" scope="col">{{ $result->incorrect_count }}</td>
                            <td class="text-center" scope="col">%{{ $result->success_rate }}</td>
                            <td class="text-center" scope="col">{{ dateFormat($result->updated_at) }}</td>
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

    @endif
</div>
