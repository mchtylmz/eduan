<?php

namespace App\Livewire\Exams;

use App\Helpers\LearningTable;
use App\Models\Exam;
use Livewire\Attributes\Locked;
use Livewire\Component;

class QuestionTable extends Component
{
    #[Locked]
    public int $examId;

    public function mount(int $examId): void
    {
        LearningTable::access('exams');
        $this->examId = $examId;
    }

    public function render()
    {
        LearningTable::access('exams');
        $record = Exam::findOrFail($this->examId);
        $options = [];

        return view('livewire.backend.exams.questions-server-table', ['record' => $record, ...$options]);
    }
}
