<div>
    <div class="bg-body-light p-2 px-3 mb-1 d-flex align-items-start">
        {{ __('İsim Soyisim') }}: <p class="mb-0 fw-semibold ms-2">{{ $log->user->display_name }}</p>
    </div>
    <div class="bg-body-light p-2 px-3 mb-1 d-flex align-items-start">
        {{ __('İşlem') }}: <p class="mb-0 fw-semibold ms-2">{{ data()->filters()->logTypes($log->log_type) }}</p>
    </div>
    <div class="bg-body-light p-2 px-3 mb-1 d-flex align-items-start">
        {{ __('Agent') }}: <p class="mb-0 fw-semibold ms-2">{{ $log->agent }}</p>
    </div>
    <div class="bg-body-light p-2 px-3 mb-1 d-flex align-items-start">
        {{ __('Tarayıcı') }}: <p class="mb-0 fw-semibold ms-2">{{ $log->browser }}</p>
    </div>
    <div class="bg-body-light p-2 px-3 mb-1 d-flex align-items-start">
        {{ __('IP') }}: <p class="mb-0 fw-semibold ms-2">{{ $log->ip }}</p>
    </div>
    <div class="bg-body-light p-2 px-3 mb-1 d-flex align-items-start">
        {{ __('Tarih') }}: <p class="mb-0 fw-semibold ms-2">{{ dateFormat($log->log_date, 'd/m/Y H:i') }}</p>
    </div>

    <div class="row mt-3 logs">
        <div class="col-lg-{{ $this->currentData ? 6 : 12 }}">
            <p class="px-3 py-2 mb-0 bg-warning-light">{{ __('Log (Eski)') }}</p>
            <table class="table w-100">
                <tbody class="w-100">
                @foreach($this->jsonData() as $key => $value)
                    <tr @class(['bg-info-light' => $this->diff($key)])>
                        <td class="py-1 bg-transparent">{{ $key }}: </td>
                        <td class="py-1 bg-transparent" style="white-space: break-spaces; word-break: break-all;">{{ !is_string($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($this->currentData)
        <div class="col-lg-6">
            <p class="px-3 py-2 mb-0 bg-success-light">{{ __('Log (Şu an)') }}</p>
            <table class="table w-100">
                <tbody class="w-100">
                @foreach($this->currentData as $key => $value)
                    <tr @class(['bg-info-light' => $this->diff($key)])>
                        <td class="py-1 bg-transparent">{{ $key }}: </td>
                        <td class="py-1 bg-transparent" style="white-space: break-spaces; word-break: break-all;">{{ !is_string($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
