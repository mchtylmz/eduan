<?php

namespace App\Livewire\Blogs;

use App\Models\Faq;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Blog;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class BlogsTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Blog::class;

    public function mount(): void
    {
        $this->setSortDesc('published_at');
    }

    public function filters(): array
    {
        return [
            $this->languageColumnFilter('locale'),
            $this->statusFilter('status'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make(__('Dil'), "language.name")
                ->sortable(),
            Column::make(__('Başlık'), "title")
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
            Column::make(__('Yayın Tarihi'), "published_at")
                ->format(fn($value) => dateFormat($value, 'd M Y, H:i'))
                ->sortable(),
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Düzenle'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('blogs:update'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.blogs.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-warning btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('blogs:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Blog yazısı silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(Blog $blog)
    {
        if (auth()->user()->cannot('blogs:delete')) {
            $this->message(__('Blog silinemedi!'))->error();
            return false;
        }

        $this->message(__('Blog başarıyla silindi!'))->success();

        return $blog->delete();
    }
}
