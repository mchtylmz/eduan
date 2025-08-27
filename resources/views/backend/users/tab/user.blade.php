<!-- user-info -->
@livewire('users.user-form', ['user' => $user])

<hr>

<div class="mb-3">
    <ul class="list-group list-group-flush">
        <li class="list-group-item">
            {{ __('Bugün Kalan Yapay Zeka Kullanım Limit') }} :
            {{ max($user->gpt_limit - $user->usageGptLimit(), 0) }} / {{$user->gpt_limit }}
        </li>
        <li class="list-group-item">
            {{ __('Yetki Grubu') }} :
            @if($user->can(\App\Enums\RoleTypeEnum::ADMIN->value))
                {{ \App\Enums\RoleTypeEnum::ADMIN->name() }}
            @else
                {{ \App\Enums\RoleTypeEnum::USER->name() }}
            @endif
        </li>
        <li class="list-group-item">{{ __('E-posta Onay Tarihi') }} : {{ $user->email_verified_at?->format('Y-m-d H:i') }}</li>
        <li class="list-group-item">{{ __('Son Güncellenme Tarihi') }} : {{ $user->updated_at ? $user->updated_at->format('Y-m-d H:i') : '' }}</li>
        <li class="list-group-item">{{ __('Kayıt Tarihi') }} : {{ $user->created_at->format('Y-m-d H:i') }}</li>
    </ul>
</div>


<!-- /user-info -->
