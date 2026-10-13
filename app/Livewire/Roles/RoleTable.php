<?php

namespace App\Livewire\Roles;

use App\Enums\RoleTypeEnum;
use App\Models\Role;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class RoleTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'roles';

    protected function builder(): Builder
    {
        return Role::with('permissions')->withCount('users');
    }

    public function render()
    {
        $record = $this->selectedRecord();
        $group = $record ? collect(RoleTypeEnum::cases())->first(fn ($type) => $record->permissions->contains('name', $type->value)) : null;

        return view('livewire.backend.roles.server-table', [
            'details' => $record ? [__('Yetki Adı') => $record->name, __('Yetki Grubu') => $group?->name() ?? '-',
                __('Kullanıcı Sayısı') => $record->users_count] : [],
        ]);
    }

    public function delete(Role $role): ?bool
    {
        $this->requireTableAccess('delete');
        if ($role->users()->count() > 0) {
            $this->message(__('Yetkiye bağlı kullanıcı bulunuyor, yetki silinemedi!'))->toast(true)->error();

            return false;
        }
        $this->message(__('Yetki silindi!'))->toast(true)->success();
        $deleted = $role->delete();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }
}
