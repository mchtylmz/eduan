<?php

namespace App\Livewire\Ai;

use App\Enums\YesNoEnum;
use App\Models\Lesson;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\AnswerAI;
use Rappasoft\LaravelLivewireTables\Views\Columns\AvgColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

#[Lazy(isolate: true)]
class AnswerAiTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = AnswerAI::class;

    public function mount(): void
    {
        // $this->setFilter('answer_a_i_s.locale', settings()->examlanguageCode ?? app()->getLocale());
        $this->setSortDesc('report');
    }

    public function builder(): Builder
    {
        return AnswerAI::with(['lesson', 'topic', 'question', 'votes'])->orderBy('report', 'DESC');
    }

    public function filters(): array
    {
        return [
            $this->activeLessonFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('answer_a_i_s.lesson_id', $value)
            ),
            $this->activeTopicFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('answer_a_i_s.topic_id', $value)
            ),
            $this->languageColumnFilter('answer_a_i_s.locale'),
            SelectFilter::make(__('Raporlama'), 'report')
                ->options([
                    0 => __('Tümü'),
                    YesNoEnum::YES->value => __('Hatalı raporlananlar')
                ])
                ->filter(function (Builder $builder, int $value) {
                    $builder->when($value, fn(Builder $builder, $value) => $builder->where('answer_a_i_s.report', $value));
                })
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")->hideIf(true),
            Column::make("Lesson id", "lesson_id")->hideIf(true),
            Column::make("Topic id", "topic_id")->hideIf(true),
            Column::make("Question id", "question_id")->hideIf(true),

            Column::make(__('Dil'), "language.name")
                ->sortable(),
            Column::make(__('Ders'), "lesson.name")
                ->format('getJsonLocaleValue')
                ->searchable()
                ->sortable(),
            Column::make(__('Konu'), "topic.title")
                ->collapseOnMobile()
                ->format('getJsonLocaleValue')
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Soru'), "question.attachment")
                ->component('table.image')
                ->attributes(fn($value, $row, Column $column) => [
                    'image' => getImage($value)
                ])
                ->searchable()
                ->sortable(),
            CountColumn::make(__('Değerlendirme'))
                ->setDataSource('votes')
                ->sortable(),
            AvgColumn::make(__('Ortalama'))
                ->setDataSource('votes', 'vote')
                ->sortable(),
            CountColumn::make(__('Görüntüleme'))
                ->setDataSource('usages')
                ->sortable(),
            Column::make(__('Oluşturma'), "created_at")
                ->collapseOnMobile()
                ->format(fn($value) => $value ? dateFormat($value, 'd/m/Y H:i') : '-')
                ->sortable(),
            ComponentColumn::make(__('Rapor'), "report")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn ($value, $row, Column $column) => [
                    'type' => YesNoEnum::YES->is($value) ? 'danger' : 'success',
                    'label' => YesNoEnum::YES->is($value) ? __('Raporlandı') : __('Raporlanmadı'),
                ])
                ->searchable()
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Detay'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-magic-wand-sparkles mx-1"></i> %s', __('Görüntüle')))
                ->location(fn($row) => route('admin.ai.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html()
        ];
    }
}
