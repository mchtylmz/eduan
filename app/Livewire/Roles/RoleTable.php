<?php

namespace App\Livewire\Roles;

use App\Enums\RoleTypeEnum;
use App\Traits\CustomLivewireAlert;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;
use App\Models\Role;

#[Lazy(isolate: true)]
class RoleTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure;

    protected $model = Role::class;

    public function builder(): Builder
    {
        return Role::with(['users']);
    }

    public function columns(): array
    {
        return [
            Column::make(__('Yetki Adı'), "name")
                ->searchable()
                ->sortable(),
            Column::make(__('Yetki Grubu'))
                ->label(function ($row) {
                    if ($row->hasPermissionTo(RoleTypeEnum::ADMIN)) {
                        return RoleTypeEnum::ADMIN->name();
                    }
                    if ($row->hasPermissionTo(RoleTypeEnum::USER)) {
                        return RoleTypeEnum::USER->name();
                    }

                    return '-';
                }),
            CountColumn::make(__('Kullanıcı Sayısı'))
                ->setDataSource('users')
                ->sortable(),
            /*ArrayColumn::make(__('İşlem izinleri'), 'id')
                ->data(fn($value, $row) => $row->permissions)
                ->outputFormat(fn($index, $value) => $value->name)
                ->separator(', ')
                ->emptyValue('')*/
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Düzenle'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('roles:update'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.roles.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-warning btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('roles:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Yetki silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("'.$row->id.'")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(Role $role): ?bool
    {
        if (auth()->user()->cannot('roles:delete')) {
            $this->message(__('Yetki silinemedi, yetkiniz bulunmuyor!'))->toast(true)->error();
            return false;
        }

        if ($role->users()->count() > 0) {
            $this->message(__('Yetkiye bağlı kullanıcı bulunuyor, yetki silinemedi!'))->toast(true)->error();
            return false;
        }

        $this->message(__('Yetki silindi!'))->toast(true)->success();
        return $role->delete();
    }

}
