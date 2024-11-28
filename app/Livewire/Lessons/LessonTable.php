<?php

namespace App\Livewire\Lessons;

use App\Enums\StatusEnum;
use App\Models\Language;
use App\Models\Lesson;
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
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

#[Lazy(isolate: true)]
class LessonTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Lesson::class;

    public function mount(): void
    {
        $this->setFilter('locale', settings()->examlanguageCode ?? app()->getLocale());
        //$this->setSortAsc('sort');
    }

    public function builder(): Builder
    {
        return Lesson::query();
    }

    public function filters(): array
    {
        return [
            $this->languageFilter('name'),
            $this->statusFilter('lessons.status'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')
                ->hideIf(true),
            Column::make(__('Kodu'), "code")
                ->searchable()
                ->sortable(),
            Column::make(__('Ders Adı'), "name")
                ->searchable()
                ->sortable(),
            CountColumn::make(__('Konu Sayısı'))
                ->setDataSource('topics')
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
                ->hideIf(auth()?->user()->cannot('lessons:update'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.lessons.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-warning btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('lessons:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Ders silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(Lesson $lesson)
    {
        if (auth()->user()->cannot('lessons:delete')) {
            $this->message(__('Ders silinemedi!'))->error();
            return false;
        }

        if ($lesson->topics()->count()) {
            $this->message(__('Ders silinemez, bağlı konu bulunuyor!'))->error();
            return false;
        }

        if ($lesson->questions()->count()) {
            $this->message(__('Ders silinemez, bağlı soru bulunuyor!'))->error();
            return false;
        }

        $this->message(__('Ders başarıyla silindi!'))->success();

        return $lesson->delete();
    }
}
