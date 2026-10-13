<?php

namespace App\Livewire\Faqs;

use App\Helpers\TableCache;
use App\Models\Faq;
use App\Models\Language;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class FaqTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'pages';

    protected string $tableName = 'faqs';

    protected function builder(): Builder
    {
        return Faq::query()->with('language');
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.faqs.server-table', [
            'languages' => TableCache::options('content-languages', fn () => Language::orderBy('id')->pluck('name', 'code')->all()),
            'details' => $record ? [__('Dil') => $record->language?->name ?? $record->locale, __('Başlık') => $record->title, __('Sıra') => $record->sort, __('Durum') => $record->status->name()] : [],
            'editUrl' => $record && auth()->user()->can('pages:update') ? route('admin.pages.faqs.edit', $record) : null,
        ]);
    }

    public function delete(Faq $faq)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('pages:delete')) {
            $this->message(__('Soru silinemedi!'))->error();

            return false;
        }

        $this->message(__('Soru başarıyla silindi!'))->success();

        $deleted = $faq->delete();
        $this->dispatch('refresh-server-table', table: 'faqs');

        return $deleted;
    }
}
