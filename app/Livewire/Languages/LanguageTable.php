<?php

namespace App\Livewire\Languages;

use App\Models\Language;
use App\Models\Lesson;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Faq;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\CountColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class LanguageTable extends DataTableComponent
{
    protected $model = Language::class;

    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    public function filters(): array
    {
        return [
            $this->statusFilter('status'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')
                ->hideIf(true),
            Column::make(__('Kodu'), "code")
                ->format(fn($value) => str($value)->upper())
                ->searchable()
                ->sortable(),
            Column::make(__('Adı'), "name")
                ->searchable()
                ->sortable(),
            ComponentColumn::make(__('Varsayılan'), 'status')
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => settings()->defaultLocale == $row->code ? 'success' : 'dark',
                    'label' => settings()->defaultLocale == $row->code ? __('Evet') : __('Hayır'),
                ]),
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
            LinkColumn::make(__('Çeviriler'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('languages:translate'))
                ->title(fn($row) => sprintf('<i class="fa fa-language mx-1"></i> %s', __('Çeviriler')))
                ->location(fn($row) => route('admin.languages.translate', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),

            LinkColumn::make(__('Düzenle'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('languages:update'))
                ->title(fn($row) => sprintf('<i class="fa fa-pen mx-1"></i> %s', __('Düzenle')))
                ->location(fn($row) => route('admin.languages.edit', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-warning btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('languages:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Dil ve çeviriler kalıcı olarak silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(Language $language)
    {
        if (auth()->user()->cannot('languages:delete')) {
            $this->message(__('Dil silinemedi!'))->error();
            return false;
        }

        if (Language::all()->count() <= 1) {
            $this->message(__('Dil silinemez, sistemde en az 1 dil olmalıdır!'))->error();
            return false;
        }

        if (settings()->defaultLocale == $language->code) {
            $this->message(__('Varsayılan sistem dili silinemez!'))->error();
            return false;
        }

        if (file_exists(lang_path($language->code) . '.json')) {
            unlink(lang_path($language->code) . '.json');
        }

        $this->message(__('Dil ve çeviriler silindi!'))->success();

        return $language->delete();
    }
}
