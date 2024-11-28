<?php

namespace App\Livewire\Users;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\User;
use App\Models\Role;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectDropdownFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

#[Lazy(isolate: true)]
class UserTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = User::class;

    public function builder(): Builder
    {
        return User::with(['roles']);
    }

    public function filters(): array
    {
        return [
            $this->roleFilter(),
            $this->emailVerifiedFilter('users.email_verified'),
            $this->statusFilter('users.status'),
            $this->lastLoginDateFilter('users.last_login_at'),
            $this->registrationDateFilter('users.created_at'),
        ];
    }

    public function columns(): array
    {
        return [
            $this->hiddenColumn(),
            $this->roleColumn(),
            $this->usernameColumn(),
            $this->nameColumn(),
            $this->surnameColumn(),
            $this->emailVerifiedColumn(),
            $this->statusColumn(),
            $this->lastLoginColumn(),
        ];
    }

    protected function hiddenColumn(): Column
    {
        return Column::make('Id', 'id')->hideIf(true);
    }

    protected function roleColumn(): ArrayColumn
    {
        return ArrayColumn::make(__('Yetki'), 'name')
            ->data(fn($value, $row) => $row->getRoleNames())
            ->outputFormat(fn($index, $value) => $value)
            ->separator('<br>')
            ->emptyValue('-');
    }

    protected function usernameColumn(): Column
    {
        return Column::make(__('Kullanıcı Adı'), "username")
            ->searchable()
            ->sortable();
    }

    protected function nameColumn(): Column
    {
        return Column::make(__('İsim'), "name")
            ->searchable()
            ->collapseOnMobile()
            ->sortable();
    }

    protected function surnameColumn(): Column
    {
        return Column::make(__('Soyisim'), "surname")
            ->searchable()
            ->collapseOnMobile()
            ->sortable();
    }

    protected function statusColumn(): Column
    {
        return ComponentColumn::make(__('Durum'), "status")
            ->collapseOnMobile()
            ->component('table.status')
            ->attributes(fn ($value, $row, Column $column) => [
                'type' => $value->class(),
                'label' => $value->name(),
            ])
            ->searchable()
            ->sortable();
    }

    protected function emailVerifiedColumn(): Column
    {
        return ComponentColumn::make(__('E-posta Onay'), "email_verified")
            ->collapseOnMobile()
            ->component('table.status')
            ->attributes(fn ($value, $row, Column $column) => [
                'type' => YesNoEnum::YES->is($value) ? 'success fw-medium' : 'danger fw-medium',
                'label' => YesNoEnum::YES->is($value) ? __('Onaylı') : __('Onaylı Değil'),
            ])
            ->searchable()
            ->sortable();
    }

    protected function lastLoginColumn(): Column
    {
        return Column::make(__('Son Giriş'), "last_login_at")
            ->collapseOnMobile()
            ->format(fn($value) => $value ? dateFormat($value) : '-')
            ->sortable();
    }

    public function appendColumns(): array
    {
        return [
            $this->editButton(),
            $this->deleteButton(),
        ];
    }

    protected function editButton(): LinkColumn
    {
        return LinkColumn::make(__('Düzenle'))
            ->collapseOnMobile()
            ->title(fn($row) => sprintf('<i class="fa fa-user mx-1"></i> %s', __('Görüntüle')))
            ->location(fn($row) => route('admin.users.edit', $row->id))
            ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
            ->html();
    }

    protected function deleteButton(): WireLinkColumn
    {
        return WireLinkColumn::make(__('Kaldır'))
            ->collapseOnMobile()
            ->hideIf(auth()?->user()->cannot('users:delete'))
            ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Kaldır')))
            ->confirmMessage(__('Kullanıcının giriş yapabilme özelliği kapatılacaktır, işleme devam edilsin mi?'))
            ->action(fn($row) => 'delete("'.$row->id.'")')
            ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
            ->html();
    }

    public function delete(User $user): ?bool
    {
        if (auth()->user()->cannot('users:delete')) {
            $this->message(__('Kullanıcı silinemez, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        if ($user->id == auth()->id()) {
            $this->message(__('Kendi kullanıcınızı kaldıramazsınız!'))->error();
            return false;
        }

        $this->message(__('Kullanıcı giriş yapabilme kaldırıldı!'))->success();

        return $user->delete();
    }
}
