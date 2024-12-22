<?php

namespace App\Livewire\Exams;

use App\Enums\StatusEnum;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Topic;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectDropdownFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

#[Lazy(isolate: true)]
class QuestionTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Question::class;

    public int $defaultPerPage = 5;

    public int $examId;

    public function mount(int $examId): void
    {
        $this->examId = $examId;
        $this->resetPage($this->getComputedPageName());
        $this->clearSearch();

        //$this->setFilter('status', StatusEnum::ACTIVE->value);
        $this->setSortAsc('status');
    }

    public function builder(): Builder
    {
        return Exam::find($this->examId)?->questions()->select('*, order')->getQuery();
    }

    public function filters(): array
    {
        return [
            $this->statusFilter('questions.status'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make(__('Dil'), "language.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Dil'), "language.name")
                ->searchable()
                ->sortable(),
            Column::make(__('Ders'), "lesson.name")
                ->format('getJsonLocaleValue')
                ->searchable()
                ->sortable(),
            Column::make(__('Konu'), 'topic.title')
                ->format('getJsonLocaleValue')
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            LinkColumn::make(__('Soru'), 'attachment')
                ->collapseOnMobile()
                ->title(function ($row) {
                    if ($row->attachment) {
                        return sprintf(
                            '<i class="fa fa-external-link mx-1"></i> %s', __('Görüntüle')
                        );
                    }
                    return '-';
                })
                ->location(fn($row) => asset($row->attachment_url))
                ->attributes(fn($row) => ['class' => 'text-dark', 'target' => '_blank'])
                ->html(),
            Column::make(__('Süre'), "time")
                ->collapseOnMobile()
                ->format(function ($value) {
                    return sprintf('%d %s', $value, __('sn'));
                })
                ->sortable(),
            ComponentColumn::make(__('Durum'), "status")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn ($value, $row, Column $column) => [
                    'type' => $value->class(),
                    'label' => $value->name(),
                ])
                ->searchable()
                ->sortable(),
        ];
    }
}
