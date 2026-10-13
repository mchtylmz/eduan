<?php

namespace App\Livewire\Exams;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\Exam;
use App\Models\Season;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResultsTable extends Component
{
    #[Locked]
    public ?Exam $exam = null;

    #[Locked]
    public ?User $user = null;

    #[Locked]
    public bool $incorrectCount = false;

    public function mount(?Exam $exam = null, ?User $user = null, bool $incorrectCount = false): void
    {
        LearningTable::access('exams');
        $this->exam = $exam;
        $this->user = $user;
        $this->incorrectCount = $incorrectCount;
    }

    public function render()
    {
        LearningTable::access('exams');
        $students = LearningTable::studentIds();
        $scope = hash('sha256', json_encode([$students, $this->exam?->id]));
        $query = LearningTable::resultsQuery($students)->when($this->exam, fn ($query) => $query->where('exam_id', $this->exam->id));

        return view('livewire.backend.exams.results-server-table', [
            'seasons' => [0 => __('tables.no_season')] + TableCache::options('seasons', fn () => Season::orderByDesc('id')->pluck('name', 'id')->all()),
            'users' => TableCache::options('result-users-'.$scope, fn () => User::whereIn('id', (clone $query)->select('user_id'))->reorder()->orderByDesc('id')->get(['id', 'name', 'surname', 'email'])
                ->mapWithKeys(fn ($user) => [$user->id => sprintf('(%s) %s', $user->email, $user->display_name)])->all()),
            'exams' => TableCache::options('result-exams-'.$scope, fn () => Exam::whereIn('id', (clone $query)->select('exam_id'))->get(['id', 'name'])->pluck('name', 'id')->all()),
        ]);
    }

    public function showDetail(int $id): void
    {
        LearningTable::access('exams');
        $query = LearningTable::resultsQuery(LearningTable::studentIds());
        if ($this->exam) {
            $query->where('exam_id', $this->exam->id);
        }
        if ($this->user) {
            $query->where('user_id', $this->user->id);
        }
        if ($this->incorrectCount) {
            $query->whereColumn('question_count', 'correct_count');
        }
        $result = $query->findOrFail($id);
        $this->dispatch('open-learning-table', view: 'exams.results-detail-table', recordId: $result->id);
    }
}
