<?php

namespace App\Livewire\Users;

use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\User;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

class StudentsTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = User::class;

    public int $defaultPerPage = 10;

    public int $teacherId;

    public function mount(int $teacherId): void
    {
        $this->teacherId = $teacherId;
        $this->resetPage($this->getComputedPageName());
        $this->clearSorts();
    }

    public function builder(): Builder
    {
        return User::find($this->teacherId)?->students()->getQuery();
    }

    public function columns(): array
    {
        return [
            $this->hiddenColumn(),
            $this->usernameColumn(),
            $this->nameColumn(),
            $this->surnameColumn(),
            $this->statusColumn(),
        ];
    }

    protected function hiddenColumn(): Column
    {
        return Column::make('Id', 'id')->hideIf(true);
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

    public function appendColumns(): array
    {
        return [
            $this->deleteButton()
        ];
    }

    protected function deleteButton(): WireLinkColumn
    {
        return WireLinkColumn::make(__('Kaldır'))
            ->collapseOnMobile()
            ->hideIf(auth()?->user()->cannot('users:student-delete'))
            ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Kaldır')))
            ->confirmMessage(__('Öğrencinin/Kullanıcının öğretmen ataması/eşleşmesi kaldırılacaktır, işleme devam edilsin mi?'))
            ->action(fn($row) => 'delete("'.$row->id.'")')
            ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
            ->html();
    }

    public function delete(User $user): ?bool
    {
        if (auth()->user()->cannot('users:student-delete')) {
            $this->message(__('Öğrencinin/Kullanıcının öğretmen ataması/eşleşmesi kaldırılamaz, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        if (!$user->teachers()->find($this->teacherId)) {
            $this->message(__('Kendi öğrenciniz olmayan kullanıcıyı kaldıramazsınız!'))->error();
            return false;
        }

        $this->message(__('Öğrencinin/Kullanıcının öğretmen ataması/eşleşmesi kaldırıldı!'))->success();

        return $user->teachers()->detach($this->teacherId);
    }
}
