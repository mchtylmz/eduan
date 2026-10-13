<?php

namespace App\Livewire\Tests;

use App\Helpers\LearningTable;
use App\Helpers\TableCache;
use App\Models\Season;
use App\Models\Test;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ResultsTable extends Component
{
    #[Locked]
    public ?Test $test = null;

    #[Locked]
    public ?User $user = null;

    #[Locked]
    public bool $incorrectCount = false;

    public function mount(?Test $test = null, ?User $user = null, bool $incorrectCount = false): void
    {
        LearningTable::access('tests');
        $this->test = $test;
        $this->user = $user;
        $this->incorrectCount = $incorrectCount;
    }

    public function render()
    {
        LearningTable::access('tests');
        $students = LearningTable::studentIds();
        $scope = hash('sha256', json_encode([$students, $this->test?->id]));
        $query = LearningTable::timedResultsQuery($students)->when($this->test, fn ($query) => $query->where('test_id', $this->test->id));

        return view('livewire.backend.tests.results-server-table', [
            'seasons' => [0 => __('tables.no_season')] + TableCache::options('seasons', fn () => Season::orderByDesc('id')->pluck('name', 'id')->all()),
            'users' => TableCache::options('timed-result-users-'.$scope, fn () => User::whereIn('id', (clone $query)->select('user_id'))->reorder()->orderByDesc('id')->get(['id', 'name', 'surname', 'email'])
                ->mapWithKeys(fn ($user) => [$user->id => sprintf('(%s) %s', $user->email, $user->display_name)])->all()),
            'tests' => TableCache::options('timed-result-tests-'.$scope, fn () => Test::whereIn('id', (clone $query)->select('test_id'))->get(['id', 'name'])->pluck('name', 'id')->all()),
        ]);
    }

    public function showDetail(int $id): void
    {
        LearningTable::access('tests');
        $query = LearningTable::timedResultsQuery(LearningTable::studentIds());
        if ($this->test) {
            $query->where('test_id', $this->test->id);
        }
        if ($this->user) {
            $query->where('user_id', $this->user->id);
        }
        if ($this->incorrectCount) {
            $query->whereColumn('question_count', 'correct_count');
        }
        $result = $query->findOrFail($id);
        $this->dispatch('open-learning-table', view: 'tests.results-detail-table', recordId: $result->id);
    }
}
