<?php

namespace App\Livewire\Exams;

use App\Enums\YesNoEnum;
use App\Models\ExamResult;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ExamResultDetail;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

#[Lazy(isolate: true)]
class ResultsDetailTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = ExamResultDetail::class;

    public int $defaultPerPage = 5;
    public int $examResultId;

    public function mount(int $examResultId): void
    {
        $this->examResultId = $examResultId;

        $this->resetPage($this->getComputedPageName());
        $this->clearSorts();
        $this->clearFilterEvent();
    }

    public function builder(): Builder
    {
        return ExamResultDetail::with(['question', 'answer', 'lesson', 'topic'])
            ->where('exam_result_id', $this->examResultId);
    }

    public function filters(): array
    {
        return [
            $this->activeLessonInResultFilter(
                $this->examResultId,
                fn(Builder $builder, array $value) => $builder->whereIn('exam_result_details.lesson_id', $value)
            ),
            $this->activeTopicInResultFilter(
                $this->examResultId,
                fn(Builder $builder, array $value) => $builder->whereIn('exam_result_details.topic_id', $value)
            ),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            ComponentColumn::make(__('Soru'), "question.attachment")
                ->component('table.image')
                ->attributes(fn($value, $row, Column $column) => [
                    'image' => getImage($value)
                ])
                ->searchable()
                ->sortable(),
            Column::make(__('Yanıt'), "answer.title")
                ->sortable(),
            ComponentColumn::make(__('Yanıt'), "correct")
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => YesNoEnum::YES->is($value) ? 'success' : 'danger',
                    'label' => YesNoEnum::YES->is($value) ? __('Doğru') : __('Yanlış')
                ])
                ->searchable()
                ->sortable(),
            Column::make(__('Ders'), "lesson.name")
                ->collapseOnMobile()
                ->format('getJsonLocaleValue')
                ->sortable(),
            Column::make(__('Konu'), "topic.title")
                ->collapseOnMobile()
                ->format('getJsonLocaleValue')
                ->sortable(),
            Column::make(__('Süre'), "time")
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Tarih'), "updated_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y, H:i') : '-')
                ->sortable(),
        ];
    }
}
