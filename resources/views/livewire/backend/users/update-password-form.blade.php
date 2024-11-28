<div class="pb-3">
    <div class="alert alert-warning fw-bold">
        <small>Parola en az 6 karakter olmalı. En az 1 harf veya rakam içermelidir.</small>
    </div>

    <x-badge.errors/>
    <form wire:submit="save">
        <div class="mb-3">
            <label class="form-label" for="newPassword">{{ __('Yeni Parola') }}</label>
            <input type="text" class="form-control" id="newPassword" placeholder="*******" wire:model="newPassword" minlength="6" autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label" for="newPasswordConfirmation">{{ __('Tekrar Yeni Parola') }}</label>
            <input type="text" class="form-control" id="newPasswordConfirmation" placeholder="*******" wire:model="newPasswordConfirmation" minlength="6" autocomplete="off">
        </div>

        <div class="text-center mb-3">
            <button type="submit" class="btn btn-alt-primary px-4 mt-2" wire:loading.attr="disabled">
                <div wire:loading.remove>
                    <i class="fa fa-fw fa-save me-1 opacity-50"></i> {{ __('Parola Güncelle') }}
                </div>
                <div wire:loading>
                    <i class="fa fa-fw fa-spinner fa-pulse mx-1" style="animation-duration: .5s"></i>
                </div>
            </button>
        </div>
    </form>
</div>
