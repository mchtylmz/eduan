<?php

namespace App\Livewire\Leagues;

use App\Enums\RoleTypeEnum;
use App\Enums\StatusEnum;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\LeagueResult;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

#[Lazy(isolate: true)]
class LeagueResultsTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = LeagueResult::class;

    public int $leagueId;

    public function mount(int $leagueId): void
    {
        $this->leagueId = $leagueId;

        $this->resetPage($this->getComputedPageName());
        $this->setSortAsc('league_number');
        $this->setSortAsc('position');
        $this->clearFilterEvent();
    }

    public function builder(): Builder
    {
        return LeagueResult::with(['user'])
            ->where('league_id', $this->leagueId)
            ->when(
                auth()->user()->can(RoleTypeEnum::TEACHER->value),
                fn ($query) => $query->whereIn('user_id', auth()->user()->students()->select('id'))
            );
    }

    public function filters(): array
    {
        return [
            $this->usersFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('league_results.user_id', $value)
            ),
            SelectFilter::make(__('Lig'), 'league_number')
                ->options([
                    '' => __('Tümü'),
                    1 => __('1. Lig'),
                    2 => __('2. Lig'),
                    3 => __('3. Lig'),
                    4 => __('4. Lig'),
                    5 => __('5. Lig'),
                    6 => __('6. Lig'),
                    7 => __('7. Lig'),
                ])
                ->filter(function (Builder $builder, int $value)  {
                    $builder->where('league_number', $value);
                })
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")->hideIf(true),
            Column::make(__('İsim'), "user.name")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Soyisim'), "user.surname")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Doğru'), "total_correct")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Yanlış'), "total_incorrect")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Lig'), "league_number")
                ->format(fn($value) => match ($value) {
                    1 => __('1. Lig'),
                    2 => __('2. Lig'),
                    3 => __('3. Lig'),
                    4 => __('4. Lig'),
                    5 => __('5. Lig'),
                    6 => __('6. Lig'),
                    7 => __('7. Lig'),
                    default => __('Lig')
                })
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Sıra'), "position")
                ->searchable()
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Puan'), "total_score")
                ->searchable()
                ->sortable(),
        ];
    }
}
