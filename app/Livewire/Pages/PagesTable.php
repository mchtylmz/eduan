<?php

namespace App\Livewire\Pages;

use App\Enums\PageTypeEnum;
use App\Models\Page;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class PagesTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'pages';

    protected string $tableName = 'pages';

    protected function builder(): Builder
    {
        return Page::query();
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.pages.server-table', [
            'details' => $record ? [__('Başlık') => $record->title, __('Sıra') => $record->sort, __('Yönlendirme') => $record->link ?: '-', __('Durum') => $record->status->name()] : [],
            'editUrl' => $record && auth()->user()->can('pages:update') && ! PageTypeEnum::SYSTEM->is($record->type) ? route('admin.pages.edit', $record) : null,
        ]);
    }

    public function delete(Page $page)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('pages:delete')) {
            $this->message(__('Sayfa silinemedi!'))->error();

            return false;
        }

        if (PageTypeEnum::SYSTEM->is($page->type)) {
            $this->message(__('Sistem sayfaları silinemez!'))->error();

            return false;
        }

        $this->message(__('Sayfa başarıyla silindi!'))->success();

        $deleted = $page->delete();
        $this->dispatch('refresh-server-table', table: 'pages');

        return $deleted;
    }
}
