<?php

namespace App\Livewire\Tests;

use App\Helpers\LearningTable;
use App\Models\Test;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class TestTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'tests';

    protected function builder(): Builder
    {
        return Test::with('language')->withCount('questions');
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.tests.server-table', [
            ...LearningTable::options(),
            'details' => $record ? [__('Adı') => $record->name, __('Kodu') => $record->code, __('Dil') => $record->language?->name ?? $record->locale,
                __('Soru Sayısı') => $record->questions_count, __('Süre') => formatSecondToTime(secondToTime($record->duration), hideSeconds: true), __('Geçme') => $record->passing_score,
                __('Görüntüleme') => $record->hits, __('Durum') => $record->status->name()] : [],
        ]);
    }

    public function delete(Test $test): ?bool
    {
        $this->requireTableAccess('delete');
        $deleted = $test->delete();
        $this->message(__('Sınav silindi!'))->success();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }
}
