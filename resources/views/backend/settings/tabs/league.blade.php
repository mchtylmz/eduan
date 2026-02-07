@php
    $daysOfWeek = [
        1 => __('Pazartesi'),
        2 => __('Salı'),
        3 => __('Çarşamba'),
        4 => __('Perşembe'),
        5 => __('Cuma'),
        6 => __('Cumartesi'),
        0 => __('Pazar')
    ];

    $leagueResultModel = [
        'App\Models\TestsResult' => __('Sınav Sonuçları'),
        'App\Models\ExamResult' => __('Test Sonuçları'),
    ];

    $leagueResultCompleted = [
        0 => __('Tüm Sonuçlar'),
        1 => __('Tamamlandı / Tamamlanan Sonuçlar'),
    ];

@endphp

    <!-- form -->
<form class="js-validation" action="{{ route('admin.settings.store') }}" method="POST"
      enctype="multipart/form-data">

    <div class="alert alert-info">
        {{ __('Lig sonuçları haftalık olarak yayınlanır. Sonuç yayınlanma zamanı geldiğinde önceki haftanın sonuçları yayınlanır.') }}
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueStatus">{{ __('Durum') }}</label>
            <select id="gptModel" class="form-control selectpicker" data-live-search="true" data-size="10"
                    name="settings[leagueStatus]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(\App\Enums\StatusEnum::options() as $optionKey => $optionText)
                    <option
                        value="{{ $optionKey }}" @selected($optionKey == (settings()->leagueStatus ?? 'passive'))>{{ $optionText }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueCorrectPoint">{{ __('Doğru Yanıt Puanı') }}</label>
            <input type="number" min="1" max="99" class="form-control" id="leagueCorrectPoint"
                   name="settings[leagueCorrectPoint]" placeholder="{{ __('Puan') }}.."
                   value="{{ settings()->leagueCorrectPoint ?? 3 }}" required>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueIncorrectPoint">{{ __('Yanlış Yanıt Puanı') }}</label>
            <input type="number" min="-99" max="99" class="form-control" id="leagueIncorrectPoint"
                   name="settings[leagueIncorrectPoint]" placeholder="{{ __('Puan') }}.."
                   value="{{ settings()->leagueIncorrectPoint ?? -1 }}" required>
        </div>

        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueUserCount">{{ __('Ligde gösterilecek kullanıcı sayısı') }}</label>
            <input type="number" min="1" max="50" class="form-control" id="leagueUserCount"
                   name="settings[leagueUserCount]" placeholder="{{ __('Puan') }}.."
                   value="{{ settings()->leagueUserCount ?? 10 }}" required>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueDay">{{ __('Lig sonucu hesaplama (Gün)?') }}</label>
            <select id="gptModel" class="form-control selectpicker" data-live-search="true" data-size="10"
                    name="settings[leagueDay]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach($daysOfWeek as $dayKey => $dayText)
                    <option
                        value="{{ $dayKey }}" @selected($dayKey == (settings()->leagueDay ?? 6))>{{ $dayText }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueHour">{{ __('Lig sonucu hesaplama (Saat)?') }}</label>
            <select id="gptModel" class="form-control selectpicker" data-live-search="true" data-size="10"
                    name="settings[leagueHour]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach(range(0, 23) as $hour)
                    @php($hour = $hour < 10 ? '0'. $hour : $hour)
                    @php($hour .= ':00')
                    <option
                        value="{{ $hour }}" @selected($hour == (settings()->leagueHour ?? '09:00'))>{{ $hour }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueResultModel">{{ __('Hesaplama Yapılacak Tablo') }}</label>
            <select id="gptModel" class="form-control selectpicker" data-live-search="true" data-size="10"
                    name="settings[leagueResultModel]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach($leagueResultModel as $resultModel => $resultText)
                    <option
                        value="{{ $resultModel }}" @selected($resultModel == (settings()->leagueResultModel ?? ''))>{{ $resultText }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueResultCompleted">{{ __('Sonuç hesaplama durumu') }}</label>
            <select id="gptModel" class="form-control selectpicker" data-live-search="true" data-size="10"
                    name="settings[leagueResultCompleted]" required>
                <option value="" hidden>{{ __('Seçiniz') }}</option>
                @foreach($leagueResultCompleted as $key => $data)
                    <option
                        value="{{ $key }}" @selected($key == (settings()->leagueResultCompleted ?? 1))>{{ $data }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-12">
            <hr>
            <div class="alert alert-info">
                {{ __('Lig sonuçları kaç gün önceden hesap yapılacağını belirler. Örneğin 7 yazıldıysa, lig hesaplama sistemi otomatik çalıştığında başlangıç tarihini 7 gün önceden hesaplamaya başlar. Bitiş tarihi hesaplamanın yapıldığı gün olur.') }}
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <label class="form-label" for="leagueStartDays">{{ __('Lig sonucu hesaplama gün aralığı') }}</label>
            <input type="number" min="1" max="99" class="form-control" id="leagueStartDays"
                   name="settings[leagueStartDays]" placeholder="{{ __('Gün') }}.."
                   value="{{ settings()->leagueStartDays ?? 7 }}" required>
            <small>{{ __('Örnek') }}:<br>{{ __('Başlangıç tarihi') }}: {{ now()->subDays(settings()->leagueStartDays ?? 7)->format('Y-m-d') }} <br>{{ __('Bitiş tarihi') }}: {{ now()->format('Y-m-d') }}</small>
        </div>
    </div>

    @can('settings:update')
        <div class="mb-3 text-center py-2 mt-3">
            <button type="submit" class="btn btn-alt-primary px-4">
                <i class="fa fa-save mx-2 fa-faw"></i> {{ __('Kaydet') }}
            </button>
        </div>
    @endcan
</form>
<!-- form -->
