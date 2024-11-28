<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;

#[Lazy(isolate: true)]
class FavoriteTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = User::class;

    public int $defaultPerPage = 5;

    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
        $this->resetPage($this->getComputedPageName());
        $this->clearSorts();

    }

    public function builder(): Builder
    {
        return User::find($this->userId)?->favoriteExams()->getQuery();
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')
                ->hideIf(true),
            Column::make(__('Dil'), "language.name")
                ->sortable(),
            Column::make(__('Kodu'), "code")
                ->searchable()
                ->sortable(),
            Column::make(__('Adı'), "name")
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Görünüm'), "visibility")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => 'text-dark fw-medium',
                    'label' => $value->name(),
                ])
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Durum'), "status")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => $value->class(),
                    'label' => $value->name(),
                ])
                ->searchable()
                ->sortable()
        ];
    }
}
