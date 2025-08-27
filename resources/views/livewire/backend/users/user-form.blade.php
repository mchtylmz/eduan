<div class="pb-3">
    <x-badge.errors/>

    <form wire:submit="save">

        <div class="mb-3" wire:ignore>
            <label class="form-label" for="role_id">{{ __('Kullanıcı Yetkisi') }}</label>
            <select id="role_id"
                    class="form-control selectpicker"
                    data-live-search="true"
                    data-size="10"
                    wire:model="role_id">
                <option value="" hidden>{{ __('Kullanıcı Yetkisi Seçiniz') }}</option>
                @foreach($this->roles() as $role)
                    <option value="{{ $role->id }}" @selected(!empty($user) && $user->hasRole($role))>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" for="username">{{ __('Kullanıcı Adı') }} / {{ __('E-posta Adresi') }}</label>
            <input type="email" class="form-control" id="username" wire:model="username" placeholder="{{ __('Kullanıcı Adı') }}..">
            <x-badge.error field="username"/>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label" for="name">{{ __('İsim') }}</label>
                    <input type="text" class="form-control" id="name" wire:model="name" placeholder="{{ __('İsim') }}..">
                    <x-badge.error field="name"/>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label" for="surname">{{ __('Soyisim') }}</label>
                    <input type="text" class="form-control" id="surname" wire:model="surname" placeholder="{{ __('Soyisim') }}..">
                    <x-badge.error field="surname" />
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-3">
                    <label class="form-label" for="gpt_limit">{{ __('Günlük Yapay Zeka Kullanım Limiti') }}</label>
                    <input type="number" min="0" class="form-control" id="gpt_limit" wire:model="gpt_limit" placeholder="{{ __('Kullanım Limiti') }}..">
                    <x-badge.error field="gpt_limit" />
                </div>
            </div>

            <div class="col-lg-6">
                <div class="mb-3" wire:ignore>
                    <label class="form-label" for="email_verified">{{ __('E-posta Onay') }}</label>
                    <select id="email_verified"
                            class="form-control selectpicker"
                            data-live-search="true"
                            data-size="10"
                            wire:model="email_verified">
                        <option value="" hidden>{{ __('Seçiniz') }}</option>
                        @foreach(\App\Enums\YesNoEnum::options() as $optionKey => $optionValue)
                            <option value="{{ $optionKey }}" @selected(!empty($user->email_verified) && $optionKey == $user->email_verified->value)>{{ $optionKey ? __('Onaylı') : __('Onaylı Değil') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>


        @if(empty($user) || !$user->exists)
            <div class="mb-3">
                <label class="form-label" for="password">{{ __('Parola') }}</label>
                <input type="text"
                       class="form-control"
                       id="password"
                       wire:model="password"
                       minlength="6"
                       placeholder="{{ __('Parola') }} ***"
                       autocomplete="off">
                <x-badge.error field="password"/>
            </div>
        @endif

        @can($permission)
            <div class="text-center">
                <button type="submit" class="btn btn-alt-primary px-4 mt-3" wire:loading.attr="disabled">
                    <div wire:loading.remove>
                        <i class="fa fa-fw fa-save me-1 opacity-50"></i> {{ __('Kaydet') }}
                    </div>
                    <div wire:loading>
                        <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                    </div>
                </button>
            </div>
        @endcan
    </form>
</div>
