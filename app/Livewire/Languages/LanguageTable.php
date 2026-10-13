<?php

namespace App\Livewire\Languages;

use App\Models\Language;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class LanguageTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'languages';

    protected string $tableName = 'languages';

    protected function builder(): Builder
    {
        return Language::query();
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.languages.server-table', [
            'details' => $record ? [__('Kodu') => strtoupper($record->code), __('Adı') => $record->name, __('Durum') => $record->status->name()] : [],
            'editUrl' => $record && auth()->user()->can('languages:update') ? route('admin.languages.edit', $record) : null,
        ]);
    }

    public function delete(Language $language)
    {
        $this->requireTableAccess('delete');
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

        if (file_exists(lang_path($language->code).'.json')) {
            unlink(lang_path($language->code).'.json');
        }

        $this->message(__('Dil ve çeviriler silindi!'))->success();

        $deleted = $language->delete();
        $this->dispatch('refresh-server-table', table: 'languages');

        return $deleted;
    }
}
