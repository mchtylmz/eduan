<?php

namespace App\Livewire\Questions;


use App\Models\Question;
use App\Models\Topic;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ButtonGroupColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\ImageColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class QuestionTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Question::class;

    public function mount(): void
    {
        $this->setFilter('locale', settings()->examlanguageCode ?? app()->getLocale());
        //$this->setSortAsc('sort');
    }

    public function builder(): Builder
    {
        return Question::query();
    }

    public function filters(): array
    {
        return [
            $this->activeLessonFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('questions.lesson_id', $value)
            ),
            $this->activeTopicFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('questions.topic_id', $value)
            ),
            $this->languageColumnFilter('locale'),
            $this->statusFilter('status'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')
                ->hideIf(true),
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
            Column::make(__('Kodu'), "code")
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
            /*ComponentColumn::make(__('Soru'), "attachment")
                ->collapseOnMobile()
                ->component('table.image')
                ->attributes(fn($value, $row, Column $column) => [
                    'image' => getImage($value)
                ]),*/
            Column::make(__('Sıra'), "sort")
                ->collapseOnMobile()
                ->sortable(),
            Column::make(__('Süre'), "time")
                ->collapseOnMobile()
                ->format(fn($value) => sprintf('%d %s', $value, __('sn')))
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
        return [
            ButtonGroupColumn::make(__('İşlemler'))
                ->collapseOnMobile()
                ->hideIf(!count($this->actionButtons()))
                ->buttons($this->actionButtons())
        ];
    }

    protected function actionButtons(): array
    {
        $buttons = [];

        if (request()?->user()->can('questions:update')) {
            $buttons[] = $this->editButton();
        }

        if (request()?->user()->can('questions:delete')) {
            $buttons[] = $this->deleteButton();
        }

        return $buttons;
    }

    protected function editButton(): LinkColumn
    {
        return LinkColumn::make(__('Düzenle'))
            ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
            ->location(fn($row) => route('admin.questions.edit', $row->id))
            ->attributes(fn($row) => [
                'class' => 'dropdown-item text-warning',
            ])
            ->html();
    }

    protected function deleteButton(): LinkColumn
    {
        return LinkColumn::make(__('Sil'))
            ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Kaldır / Sil')))
            ->location(fn($row) => 'javascript:void(0)')
            ->attributes(fn($row) => [
                'class' => 'dropdown-item text-danger',
                'wire:confirm' => __('Soru havuzdan silinecektir, işleme devam edilsin mi?'),
                'wire:click' => 'delete('.$row->id.')'
            ])
            ->html();
    }

    public function delete(Question $question)
    {
        if (auth()->user()->cannot('questions:delete')) {
            $this->message(__('Soru silinemedi!'))->error();
            return false;
        }

        $this->message(__('Soru başarıyla silindi!'))->success();

        return $question->delete();
    }
}
