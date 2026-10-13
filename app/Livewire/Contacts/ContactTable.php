<?php

namespace App\Livewire\Contacts;

use App\Helpers\TableCache;
use App\Models\Contact;
use App\Models\Language;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ContactTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'contacts';

    protected string $tableName = 'contacts';

    protected function builder(): Builder
    {
        return Contact::query()->with('language');
    }

    public function render()
    {
        $this->selectedRecord();

        return view('livewire.backend.contacts.server-table', [
            'languages' => TableCache::options('content-languages', fn () => Language::orderBy('id')->pluck('name', 'code')->all()),
            'details' => [],
            'editUrl' => null,
        ]);
    }

    public function delete(Contact $contact)
    {
        $this->requireTableAccess('view');
        $this->message(__('Kayıt silindi!'))->success();

        $deleted = $contact->delete();
        $this->dispatch('refresh-server-table', table: 'contacts');

        return $deleted;
    }
}
