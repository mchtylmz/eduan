<?php

namespace App\Livewire\Tests;

use App\Enums\YesNoEnum;
use App\Models\ExamResult;
use App\Models\TestsResultDetail;
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

    protected $model = TestsResultDetail::class;

    public int $defaultPerPage = 5;
    public int $testsResultId;

    public function mount(int $testsResultId): void
    {
        $this->testsResultId = $testsResultId;

        $this->resetPage($this->getComputedPageName());
        $this->clearSorts();
        $this->clearFilterEvent();
    }

    public function builder(): Builder
    {
        return TestsResultDetail::with(['question', 'answer', 'lesson', 'topic'])
            ->where('tests_result_id', $this->testsResultId);
    }

    public function filters(): array
    {
        return [
            $this->activeLessonInResultFilter(
                $this->testsResultId,
                fn(Builder $builder, array $value) => $builder->whereIn('tests_result_details.lesson_id', $value)
            ),
            $this->activeTopicInResultFilter(
                $this->testsResultId,
                fn(Builder $builder, array $value) => $builder->whereIn('tests_result_details.topic_id', $value)
            ),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make(__('Puan'), "point")
                ->collapseOnMobile()
                ->sortable(),
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
                    'type' => YesNoEnum::tryFrom($value->value)->class() ?? 'warning',
                    'label' => match ($value) {
                        YesNoEnum::YES => __('Doğru'),
                        YesNoEnum::NO => __('Yanlış'),
                        default => __('Boş'),
                    }
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
            Column::make(__('Tarih'), "updated_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y, H:i') : '-')
                ->sortable(),
        ];
    }
}
