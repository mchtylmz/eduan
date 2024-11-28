<?php

namespace App\Livewire\Newsletters;

use App\Models\Lesson;
use App\Traits\CustomLivewireAlert;
use App\Traits\CustomLivewireTableFilters;
use App\Traits\LivewireTableConfigure;
use Livewire\Attributes\Lazy;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Newsletter;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\WireLinkColumn;

#[Lazy(isolate: true)]
class NewsletterTable extends DataTableComponent
{
    use CustomLivewireAlert, LivewireTableConfigure;

    protected $model = Newsletter::class;

    public function mount(): void
    {
        $this->setSortDesc('id');
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable(),
            Column::make(__('Email'), "email")
                ->searchable()
                ->sortable(),
            Column::make(__('IP'), "ip")
                ->searchable()
                ->sortable(),
            Column::make(__('Kayıt Tarihi'), "created_at")
                ->format(fn($value) => dateFormat($value, 'd M Y, H:i'))
                ->sortable(),
        ];
    }

    public function appendColumns(): array
    {
        return [
            WireLinkColumn::make(__('Sil'))
                ->collapseOnMobile()
                ->hideIf(auth()?->user()->cannot('newsletter:delete'))
                ->title(fn($row) => sprintf('<i class="fa fa-trash-alt mx-1"></i> %s', __('Sil')))
                ->confirmMessage(__('Kayıt silinecektir, işleme devam edilsin mi?'))
                ->action(fn($row) => 'delete("' . $row->id . '")')
                ->attributes(fn($row) => ['class' => 'btn btn-danger btn-sm'])
                ->html(),
        ];
    }

    public function delete(Newsletter $newsletter)
    {
        if (auth()->user()->cannot('newsletter:delete')) {
            $this->message(__('Kayıt silinemedi!'))->error();
            return false;
        }

        $this->message(__('Kayıt silindi!'))->success();

        return $newsletter->delete();
    }
}
