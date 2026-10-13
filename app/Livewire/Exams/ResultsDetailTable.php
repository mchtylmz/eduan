<?php

namespace App\Livewire\Exams;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\Lesson;
use App\Models\Topic;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResultsDetailTable extends Component
{
    #[Locked]
    public int $examResultId;

    public function mount(int $examResultId): void
    {
        LearningTable::access('exams');
        $this->examResultId = $examResultId;
    }

    public function render()
    {
        LearningTable::access('exams');
        $students = LearningTable::studentIds();
        $record = LearningTable::resultsQuery($students)->with('user:id,name,surname,email')->findOrFail($this->examResultId);
        $details = $record->details();
        $options = [
            'lessons' => TableCache::options('result-detail-lessons-'.$record->id, fn () => Lesson::active()->whereIn('id', (clone $details)->select('lesson_id'))->get()->pluck('name', 'id')->all()),
            'topics' => TableCache::options('result-detail-topics-'.$record->id, fn () => Topic::active()->whereIn('id', (clone $details)->select('topic_id'))->get()->pluck('title', 'id')->all()),
        ];

        return view('livewire.backend.exams.result-details-server-table', ['record' => $record, ...$options]);
    }
}
