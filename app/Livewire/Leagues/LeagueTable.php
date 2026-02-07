<?php

namespace App\Livewire\Leagues;

use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\League;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class LeagueTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = League::class;

    public function builder(): Builder
    {
        return League::query();
    }

    public function filters(): array
    {
        return [
            $this->dateFilter('start_at', __('Başlangıç Tarihi')),
            $this->dateFilter('end_at', __('Bitiş Tarihi')),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")->hideIf(true),
            Column::make(__('Kodu'), "code")
                ->searchable()
                ->sortable(),
            Column::make(__('Bölüm'), "label")
                ->collapseOnMobile()
                ->format(fn($value) => $value == 'tests_results' ? __('Sınav Sonuçları') : __('Test Sonuçları'))
                ->searchable()
                ->sortable(),
            Column::make(__('Başlangıç Tarihi'), "start_at")
                ->format(fn($value) => $value ? dateFormat($value, 'd M Y H:i') : '-')
                ->sortable(),
            Column::make(__('Bitiş Tarihi'), "end_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd M Y H:i') : '-')
                ->sortable(),
            Column::make(__('Hesaplanma Tarihi'), "calculated_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y, H:i') : '-')
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            WireLinkColumn::make(__('Detay'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-poll mx-1"></i> %s', __('Detay')))
                ->action(fn($row) => 'showDetail("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),
            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('leagues:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->confirmMessage(__('Lig sonuçları kalıcı olarak silinecektir, işleme devam edilsin mi?'))
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function showDetail(League $league): void
    {
        $this->dispatch(
            'showOffcanvas',
            component: [
                'leagues.league-results-table'
            ],
            data: [
                'title' => sprintf(
                    '%s - %s (%s-%s)',
                    $league->code,
                    __('Ligi Sonuç Detayları'),
                    dateFormat($league->start_at, 'd/m/Y'),
                    dateFormat($league->end_at, 'd/m/Y')
                ),
                'leagueId' => $league->id
            ]
        );
    }

    public function delete(League $league): ?bool
    {
        if (auth()->user()->cannot('leagues:delete')) {
            $this->message(__('Lig sonuçları silinemedi, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        $this->message(__('Lig sonuçları silindi!'))->success();

        $league->results()->delete();

        return $league->delete();
    }
}
