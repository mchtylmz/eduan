<?php

namespace App\Livewire\Exams;

use App\Enums\StatusEnum;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Exam;
use Rappasoft\LaravelLivewireTables\Views\Columns\ButtonGroupColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\SumColumn;

#[Lazy(isolate: true)]
class ExamTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Exam::class;

    public function mount(): void
    {
        $this->setFilter('locale', settings()->examlanguageCode ?? app()->getLocale());
        //$this->setFilter('status', StatusEnum::ACTIVE->value);
    }

    public function filters(): array
    {
        return [
            $this->activeLessonFilter(
                fn(Builder $builder, array $value) => $builder->whereHas('lessons', fn($query) => $query->where('lesson_id', $value))
            ),
            $this->activeTopicFilter(
                fn(Builder $builder, array $value) => $builder->whereHas('topics', fn($query) => $query->where('lesson_id', $value))
            ),
            $this->languageColumnFilter('exams.locale'),
            $this->visibilityFilter('exams.visibility'),
            $this->statusFilter('exams.status'),
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
            CountColumn::make(__('Sorular'))
                ->collapseOnMobile()
                ->setDataSource('questions')
                ->label(function ($row) {
                    return sprintf(
                        '<button class="btn btn-alt-secondary btn-sm" wire:click.prevent="showModal(\'%d\')">%d %s</button>',
                        $row->id,
                        $row->questions_count,
                        __('Soru')
                    );
                })
                ->html()
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
             LinkColumn::make(__('Sonuçlar'))
                 ->title(fn($row) => sprintf('<i class="fa fa-poll mx-1"></i> %s', __('Sonuçlar')))
                 ->location(fn($row) => route('admin.exams.result', $row->id))
                 ->attributes(fn($row) => ['class' => 'dropdown-item text-dark'])
                 ->html(),
             LinkColumn::make(__('Favoriye Ekleyenler'))
                 ->title(fn($row) => sprintf('<i class="fa fa-heart mx-1"></i> %s', __('Favoriye Ekleyenler')))
                 ->location(fn($row) => 'javascript:void(0)')
                 ->attributes(fn($row) => [
                     'class' => 'dropdown-item text-dark',
                     'wire:click' => 'showFavorites('.$row->id.')'
                 ])
                 ->html(),
         ];

        if (request()?->user()->can('exams:update')) {
            $actionButtons[] = LinkColumn::make(__('Düzenle'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.exams.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'dropdown-item text-warning fw-medium'])
                ->html();
        }


        if (request()?->user()->can('exams:delete')) {
            $actionButtons[] = LinkColumn::make(__('Sil'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Kaldır / Sil')))
                ->location(fn($row) => 'javascript:void(0)')
                ->attributes(fn($row) => [
                    'class' => 'dropdown-item text-danger',
                    'wire:confirm' => __('Soru silinecektir, soruya bağlı öğeler silinmeyecektir, işleme devam edilsin mi?'),
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

    public function delete(Exam $exam): ?bool
    {
        if (auth()->user()->cannot('exams:delete')) {
            $this->message(__('Test silinemedi, yetkiniz bulunmuyor!'))->error();
            return false;
        }

        $this->message(__('Test silindi!'))->success();

        return $exam->delete();
    }

    public function showModal(Exam $exam): void
    {
        $this->dispatch(
            'showOffcanvas',
            component: 'exams.question-table',
            data: [
                'title' => $exam->name,
                'examId' => $exam->id,
            ]
        );
    }

    public function showFavorites(Exam $exam): void
    {
        $this->dispatch(
            'showOffcanvas',
            component: 'exams.favorite-table',
            data: [
                'title' => sprintf('%s (%s)', __('Favoriye Ekleyenler'), $exam->name),
                'examId' => $exam->id,
            ]
        );
    }
}
