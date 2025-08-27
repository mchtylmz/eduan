<?php

namespace App\Livewire\Ai;

use App\Models\AnswerAI;
use App\Models\TestsResult;
use App\Models\UserAiUsage;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\LatexImage;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class LatexImagesTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = LatexImage::class;

    public function builder(): Builder
    {
        return LatexImage::query();
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make(__('Formül'), "formula")
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Görsel'), "image")
                ->component('table.image')
                ->attributes(fn($value, $row, Column $column) => [
                    'image' => getImage($value),
                    'class' => 'latex-ai-image'
                ])
                ->searchable()
                ->sortable(),
            Column::make(__('Oluşturma Zamanı'), "created_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y H:i') : '-')
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Güncelle'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-upload mx-1"></i> %s', __('Güncelle')))
                ->location(fn($row) => route('admin.ai.imageForm', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),
            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Latex görseli silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(LatexImage $latexImage)
    {
        $this->message(__('Latex görseli başarıyla silindi!'))->success();

        return $latexImage->delete();
    }
}
