<?php

namespace App\Livewire\Topics;

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

#[Lazy(isolate: true)]
class TopicTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Topic::class;

    public function mount(): void
    {
        $this->setFilter('locale', settings()->examlanguageCode ?? app()->getLocale());
        //$this->setSortAsc('sort');
    }

    public function builder(): Builder
    {
        return Topic::query();
    }

    public function filters(): array
    {
        return [
            $this->activeLessonFilter(
                fn(Builder $builder, array $value) => $builder->whereIn('lesson_id', $value)
            ),
            $this->languageFilter('name'),
            $this->statusFilter('topics.status'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')
                ->hideIf(true),
            Column::make(__('Ders'), "lesson.name")
                ->format(fn($value) => json_decode($value, true)[app()->getLocale()] ?? '')
                ->searchable()
                ->sortable(),
            Column::make(__('Kodu'), "code")
                ->searchable()
                ->sortable(),
            Column::make(__('Konu'), "title")
                ->searchable()
                ->sortable(),
            CountColumn::make(__('Soru Sayısı'))
                ->setDataSource('questions')
                ->sortable(),
            Column::make(__('Sıra'), "sort")
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
        return [
            LinkColumn::make(__('Düzenle'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('topics:update'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.topics.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-warning btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('topics:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Konu silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(Topic $topic)
    {
        if (auth()->user()->cannot('topics:delete')) {
            $this->message(__('Konu silinemedi!'))->error();
            return false;
        }

        if ($topic->questions()->count()) {
            $this->message(__('Konu silinemez, bağlı soru bulunuyor!'))->error();
            return false;
        }

        $this->message(__('Konu başarıyla silindi!'))->success();

        return $topic->delete();
    }
}
