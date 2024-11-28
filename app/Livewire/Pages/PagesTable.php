<?php

namespace App\Livewire\Pages;

use App\Enums\PageTypeEnum;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Page;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

class PagesTable extends DataTableComponent
{
    use CustomLivewireAlert, CustomLivewireTableFilters, LivewireTableConfigure;

    protected $model = Page::class;

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->hideIf(true),
            Column::make("type", "type")
                ->hideIf(true),
            Column::make(__('Başlık'), "title")
                ->sortable(),
            Column::make(__('Sıra'), "sort")
                ->sortable(),
            Column::make(__('Yönlendirme'), "link")
                ->format(fn($value) => $value ?: '-')
                ->sortable(),
            ComponentColumn::make(__('Durum'), "status")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => $value->class(),
                    'label' => $value->name(),
                ])
                ->searchable()
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Düzenle'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('pages:update'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.pages.edit', $row->id))
                ->attributes(fn($row) => [
                    'class' => 'btn btn-warning btn-sm ' . (PageTypeEnum::SYSTEM->is($row->type) ? 'd-none' : '')
                ])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('pages:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Sayfa silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => [
                    'class' => 'btn btn-danger btn-sm ' . (PageTypeEnum::SYSTEM->is($row->type) ? 'd-none' : '')
                ])
                ->html(),
        ];
    }

    public function delete(Page $page)
    {
        if (auth()->user()->cannot('pages:delete')) {
            $this->message(__('Sayfa silinemedi!'))->error();
            return false;
        }

        if (PageTypeEnum::SYSTEM->is($page->type)) {
            $this->message(__('Sistem sayfaları silinemez!'))->error();
            return false;
        }

        $this->message(__('Sayfa başarıyla silindi!'))->success();

        return $page->delete();
    }
}
