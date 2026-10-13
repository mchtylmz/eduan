<?php

namespace App\Livewire\Newsletters;

use App\Models\Newsletter;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class NewsletterTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'newsletter';

    protected string $tableName = 'newsletter';

    protected function builder(): Builder
    {
        return Newsletter::query();
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.newsletter.server-table', [
            'details' => $record ? [__('ID') => $record->id, __('Email') => $record->email, __('IP') => $record->ip, __('Kayıt Tarihi') => dateFormat($record->created_at, 'd M Y, H:i')] : [],
            'editUrl' => null,
        ]);
    }

    public function delete(Newsletter $newsletter)
    {
        $this->requireTableAccess('delete');
        if (auth()->user()->cannot('newsletter:delete')) {
            $this->message(__('Kayıt silinemedi!'))->error();

            return false;
        }

        $this->message(__('Kayıt silindi!'))->success();

        $deleted = $newsletter->delete();
        $this->dispatch('refresh-server-table', table: 'newsletter');

        return $deleted;
    }
}
