<?php

namespace App\Livewire\Questions;

use App\Helpers\LearningTable;
use App\Models\Question;
use App\Traits\CustomLivewireAlert;
use App\Traits\ServerTableActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class QuestionTable extends Component
{
    use CustomLivewireAlert, ServerTableActions;

    protected string $resource = 'questions';

    protected function builder(): Builder
    {
        return Question::with(['lesson', 'topic', 'language']);
    }

    public function render()
    {
        $question = $this->selectedRecord();

        return view('livewire.backend.questions.server-table', [
            ...LearningTable::options(), 'question' => $question,
            'details' => $question ? [__('Kodu') => $question->code, __('Dil') => $question->language?->name ?? $question->locale,
                __('Ders') => $question->lesson?->name ?? '-', __('Konu') => $question->topic?->title ?? '-',
                __('Sıra') => $question->sort, __('Süre') => $question->time.' '.__('sn'), __('Durum') => $question->status->name()] : [],
        ]);
    }

    public function delete(Question $question)
    {
        $this->requireTableAccess('delete');
        $deleted = $question->delete();
        $this->message(__('Soru başarıyla silindi!'))->success();
        $this->dispatch('refresh-server-table', table: $this->resource);

        return $deleted;
    }
}
