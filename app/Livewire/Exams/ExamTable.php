<?php

namespace App\Livewire\Exams;

use App\Helpers\LearningTable;
use App\Models\Exam;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ExamTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'exams';

    protected function builder(): Builder
    {
        return Exam::with('language')->withCount('questions');
    }

    public function render()
    {
        $record = $this->selectedRecord();

        return view('livewire.backend.exams.server-table', [
            ...LearningTable::options(),
            'details' => $record ? [__('Adı') => $record->name, __('Kodu') => $record->code, __('Dil') => $record->language?->name ?? $record->locale,
                __('Soru Sayısı') => $record->questions_count, __('Görünüm') => $record->visibility->name(),
                __('Görüntüleme') => $record->hits, __('Durum') => $record->status->name()] : [],
        ]);
    }

    public function delete(Exam $exam): ?bool
    {
        $this->requireTableAccess('delete');
        $deleted = $exam->delete();
        $this->message(__('Test silindi!'))->success();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }

    public function showModal(Exam $exam): void
    {
        $this->requireTableAccess();
        $this->dispatch('open-learning-table', view: 'exams.question-table', recordId: $exam->id);
    }

    public function showFavorites(Exam $exam): void
    {
        $this->requireTableAccess();
        $this->dispatch('open-learning-table', view: 'exams.favorite-table', recordId: $exam->id);
    }
}
