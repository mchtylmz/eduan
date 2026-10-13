<div class="admin-log-detail" x-data="{ onlyChanges: false }">
    @php
        $oldData = $this->jsonData();
        $newData = $this->currentData;
        $keys = array_unique([...array_keys($oldData), ...array_keys($newData)]);
    @endphp
    <dl class="admin-log-meta">
        <div><dt><i class="fa fa-user me-1" aria-hidden="true"></i>{{ __('İsim Soyisim') }}</dt><dd>{{ $log->user->display_name }}</dd></div>
        <div><dt><i class="fa fa-clock me-1" aria-hidden="true"></i>{{ __('Tarih') }}</dt><dd>{{ dateFormat($log->log_date, 'd/m/Y H:i') }}</dd></div>
        <div><dt>{{ __('İşlem') }}</dt><dd>{{ data()->filters()->logTypes($log->log_type) }} @if($log->table_name)<span class="text-muted">· {{ $log->table_name }}</span>@endif</dd></div>
        <div><dt>{{ __('IP') }}</dt><dd>{{ $log->ip }}</dd></div>
        <div class="admin-log-wide"><dt>{{ __('Tarayıcı') }}</dt><dd>{{ $log->browser }}</dd></div>
        <div class="admin-log-wide"><dt>{{ __('Agent') }}</dt><dd>{{ $log->agent }}</dd></div>
    </dl>
    <section aria-label="{{ __('management.log_fields') }}">
        <div class="admin-log-toolbar">
            <h3>{{ __('management.log_fields') }}</h3>
            @if($newData)
                <label class="form-check form-switch mb-0">
                    <input type="checkbox" class="form-check-input" x-model="onlyChanges" data-log-changes-toggle>
                    <span class="form-check-label">{{ __('management.only_changes') }}</span>
                </label>
            @endif
        </div>
        <table class="admin-log-comparison">
            <thead><tr><th scope="col">{{ __('management.field') }}</th><th scope="col">{{ __('Log (Eski)') }}</th>
                @if($newData)<th scope="col">{{ __('Log (Şu an)') }}</th>@endif</tr></thead>
            <tbody>
                @foreach($keys as $key)
                    @php $changed = $this->diff($key); @endphp
                    <tr @class(['is-changed' => $changed]) data-log-changed="{{ $changed ? 'true' : 'false' }}" x-show="!onlyChanges || {{ $changed ? 'true' : 'false' }}">
                        <th scope="row">{{ $key }}@if($changed)<i class="fa fa-circle text-info ms-1 small" aria-label="{{ __('management.changed') }}"></i>@endif</th>
                        <td data-label="{{ __('Log (Eski)') }}">{{ array_key_exists($key, $oldData) ? (is_string($oldData[$key]) ? $oldData[$key] : json_encode($oldData[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) : '-' }}</td>
                        @if($newData)<td data-label="{{ __('Log (Şu an)') }}">{{ array_key_exists($key, $newData) ? (is_string($newData[$key]) ? $newData[$key] : json_encode($newData[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) : '-' }}</td>@endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</div>
