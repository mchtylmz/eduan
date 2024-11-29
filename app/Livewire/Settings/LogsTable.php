<?php

namespace App\Livewire\Settings;

use App\Models\ExamResult;
use App\Models\Log;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectDropdownFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LogsTable extends DataTableComponent
{
    use LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Log::class;

    public function builder(): Builder
    {
        return Log::orderByDesc('id');
    }

    public function filters(): array
    {
        return [
            $this->usersFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('logs.user_id', $value)
            )
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make(__('İsim'), "user.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Soyisim'), "user.surname")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            /*
            Column::make(__('Tablo'), "table_name")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            */
            Column::make(__('İşlem'), "log_type")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            Column::make(__('Agent'), "agent")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            Column::make(__('Tarayıcı'), "browser")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            /*
            Column::make(__('Platform'), "device")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            */
            Column::make(__('IP'), "ip")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            Column::make(__('Tarih'), "log_date")
                ->collapseOnMobile()
                ->searchable()
                ->format(fn($value) => dateFormat($value, 'd/m/Y H:i'))
                ->sortable(),
        ];
    }

    public function appendColumns(): array
    {
        return [
            WireLinkColumn::make(__('Detay'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-eye mx-1"></i> %s', __('Detay')))
                ->action(fn($row) => 'showDetail("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),
        ];
    }

    public function showDetail(Log $log): void
    {
        $this->dispatch(
            'showOffcanvas',
            component: 'settings.log-detail-table',
            data: [
                'title' => __('Log Detayı'),
                'logId' => $log->id,
            ]
        );
    }
}
