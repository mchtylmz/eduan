<?php

namespace App\Livewire\Tests;

use App\Models\Exam;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Test;
use Rappasoft\LaravelLivewireTables\Views\Columns\ButtonGroupColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

#[Lazy(isolate: true)]
class TestTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Test::class;

    public function mount(): void
    {
        $this->setFilter('locale', settings()->testlanguageCode ?? app()->getLocale());
    }

    public function filters(): array
    {
        return [
            $this->languageColumnFilter('tests.locale'),
            $this->statusFilter('tests.status'),
        ];
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
            Column::make(__('Süre (saat)'), "duration")
                ->format(fn($value) => formatSecondToTime(secondToTime($value)))
                ->sortable(),
            CountColumn::make(__('bölüm Sayısı'))
                ->setDataSource('sections_with_no_parent')
                ->sortable(),
            CountColumn::make(__('Soru Sayısı'))
                ->setDataSource('questions')
                ->sortable(),
            Column::make(__('Görüntüleme'), "hits")
                ->sortable(),
            ComponentColumn::make(__('Durum'), "status")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => $value->class(),
                    'label' => $value->name(),
                ])
                ->searchable()
                ->sortable(),
        ];
    }

    public function appendColumns(): array
    {
        $actionButtons = [
            /*LinkColumn::make(__('Sonuçlar'))
                ->title(fn($row) => sprintf('<i class="fa fa-poll mx-1"></i> %s', __('Sonuçlar')))
                ->location(fn($row) => route('admin.exams.result', $row->id))
                ->attributes(fn($row) => ['class' => 'dropdown-item text-dark'])
                ->html()*/
        ];

        if (request()?->user()->can('tests:update')) {
            $actionButtons[] = LinkColumn::make(__('Düzenle'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.tests.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'dropdown-item text-warning fw-medium'])
                ->html();

            $actionButtons[] = LinkColumn::make(__('Sınav Bölümleri'))
                ->title(fn($row) => sprintf('<i class="fa fa-table-cells-large mx-1"></i> %s', __('Sınav Bölümleri')))
                ->location(fn($row) => route('admin.tests.sections', $row->id))
                ->attributes(fn($row) => ['class' => 'dropdown-item text-dark fw-medium'])
                ->html();
        }


        if (request()?->user()->can('tests:delete')) {
            $actionButtons[] = LinkColumn::make(__('Sil'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Kaldır / Sil')))
                ->location(fn($row) => 'javascript:void(0)')
                ->attributes(fn($row) => [
                    'class' => 'dropdown-item text-danger',
                    'wire:confirm' => __('Sınav silinecektir, sınava bağlı alt öğeler silinmeyecektir, işleme devam edilsin mi?'),
                    'wire:click' => 'delete('.$row->id.')'
                ])
                ->html();
        }

        return [
            ButtonGroupColumn::make(__('İşlemler'))
                ->collapseOnMobile()
                ->buttons($actionButtons),
        ];
    }

    public function delete(Test $test): ?bool
    {
        if (auth()->user()->cannot('tests:delete')) {
            $this->message(__('Sınav silinemedi, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        $this->message(__('Sınav silindi!'))->success();

        return $test->delete();
    }
}
