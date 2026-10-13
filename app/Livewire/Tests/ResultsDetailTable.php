<?php

namespace App\Livewire\Tests;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\Lesson;
use App\Models\Topic;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResultsDetailTable extends Component
{
    #[Locked]
    public int $testsResultId;

    public function mount(int $testsResultId): void
    {
        LearningTable::access('tests');
        $this->testsResultId = $testsResultId;
    }

    public function render()
    {
        LearningTable::access('tests');
        $students = LearningTable::studentIds();
        $record = LearningTable::timedResultsQuery($students)->with('user:id,name,surname,email')->findOrFail($this->testsResultId);
        $details = $record->details();
        $options = [
            'lessons' => TableCache::options('timed-result-detail-lessons-'.$record->id, fn () => Lesson::active()->whereIn('id', (clone $details)->select('lesson_id'))->get()->pluck('name', 'id')->all()),
            'topics' => TableCache::options('timed-result-detail-topics-'.$record->id, fn () => Topic::active()->whereIn('id', (clone $details)->select('topic_id'))->get()->pluck('title', 'id')->all()),
        ];

        return view('livewire.backend.tests.result-details-server-table', ['record' => $record, ...$options]);
    }
}
