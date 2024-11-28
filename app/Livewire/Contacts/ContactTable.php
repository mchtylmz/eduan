<?php

namespace App\Livewire\Contacts;

use App\Enums\YesNoEnum;
use App\Models\Newsletter;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Contact;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;

#[Lazy(isolate: true)]
class ContactTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure, CustomLivewireTableFilters;

    protected $model = Contact::class;

    public function mount(): void
    {
        $this->setSortDesc('created_at');
    }

    public function filters(): array
    {
        return [
            $this->languageColumnFilter('locale'),
            $this->hasReadFilter()
        ];
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->hideIf(true),
            Column::make(__('Dil'), "language.name")
                ->sortable(),
            Column::make(__('İsim Soyisim'), "name")
                ->sortable(),
            Column::make(__('E-posta Adresi'), "email")
                ->sortable(),
            Column::make(__('Telefon Numarası'), "phone")
                ->sortable(),
            Column::make(__('Okul Adı'), "school_name")
                ->sortable(),
            Column::make(__('Mesaj Tarihi'), "created_at")
                ->format(fn($value) => dateFormat($value, 'd M Y, H:i'))
                ->sortable(),
            ComponentColumn::make(__('Durum'), "has_read")
                ->collapseOnMobile()
                ->component('table.status')
                ->attributes(fn($value, $row, Column $column) => [
                    'type' => YesNoEnum::YES->is($value) ? 'success' : 'warning',
                    'label' => YesNoEnum::YES->is($value) ? __('Okundu') : __('Okunmamış'),
                ])
                ->searchable()
                ->sortable()
        ];
    }

    public function appendColumns(): array
    {
        return [
            LinkColumn::make(__('Görüntüle'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-eye mx-1"></i> %s', __('Görüntüle')))
                ->location(fn($row) => route('admin.contacts.detail', $row->id))
                ->attributes(fn($row) => ['class' => 'btn btn-info btn-sm'])
                ->html(),

            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Kayıt silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm ' . $row->has_read?->hidden()])
                ->html(),
        ];
    }

    public function delete(Contact $contact)
    {
        $this->message(__('Kayıt silindi!'))->success();

        return $contact->delete();
    }
}
