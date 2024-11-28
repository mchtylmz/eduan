<div>
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            @if($showNewRegister)
                <h3 class="block-title">{{ __('Yeni Kayıt olan Kullanıcılar') }}</h3>
            @endif
            @if($showLastLogins)
                <h3 class="block-title">{{ __('Son Giriş Yapan Kullanıcılar') }}</h3>
            @endif
        </div>
        <div class="block-content p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('Kullanıcı Adı') }}</th>
                        <th>{{ __('İsim') }}</th>
                        <th>{{ __('Soyisim') }}</th>
                        @if($showNewRegister)
                            <th class="text-center">{{ __('E-posta Onay') }}</th>
                        @endif
                        @if($showLastLogins)
                            <th class="text-center">{{ __('Son Giriş Zamanı') }}</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->users() as $user)
                        <tr>
                            <td class="fs-sm">{{ $user->username }}</td>
                            <td class="fs-sm">{{ $user->name }}</td>
                            <td class="fs-sm">{{ $user->surname }}</td>
                            @if($showNewRegister)
                                <td class="fs-sm text-center">
                                    {{ \App\Enums\YesNoEnum::YES->is($user->email_verified) ? __('Onaylı') : __('Onaylı Değil') }}
                                </td>
                            @endif
                            @if($showLastLogins)
                                <td class="fs-sm text-center">{{ dateFormat($user->last_login_at, 'd M Y, H:i') }}</td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
