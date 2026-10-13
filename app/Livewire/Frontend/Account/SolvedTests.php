<?php

namespace App\Livewire\Frontend\Account;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class SolvedTests extends Component
{
    use CustomLivewireAlert, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public bool $showPaginate = true;

    public bool $showNotComplete = false;

    public int $paginate = 21;

    #[Computed]
    public function tests()
    {
        if ($this->showNotComplete) {
            return Exam::with('language')
                ->withCount([
                    'questions' => fn ($query) => $query->active(),
                ])
                ->whereNotIn('id', ExamResult::where('user_id', auth()->id())->select('exam_id'))
                ->active()
                ->paginate($this->paginate);
        }

        // Match examResultStats: all personal attempts, with totals from saved answers.
        $totals = ExamResult::query()
            ->leftJoin('exam_result_details as details', 'details.exam_result_id', '=', 'exam_results.id')
            ->where('exam_results.user_id', auth()->id())
            ->selectRaw('exam_id, COUNT(DISTINCT exam_results.id) AS personal_results_count,
                COALESCE(SUM(details.correct = 1), 0) AS personal_correct_count,
                COALESCE(SUM(details.correct = 0), 0) AS personal_incorrect_count')
            ->groupBy('exam_id');

        return Exam::query()
            ->joinSub($totals, 'personal_results', fn ($join) => $join->on('personal_results.exam_id', '=', 'exams.id'))
            ->select('exams.*', 'personal_results.personal_results_count', 'personal_results.personal_correct_count', 'personal_results.personal_incorrect_count')
            ->with('language')
            ->active()
            ->paginate($this->paginate);
    }

    public function render()
    {
        return view('livewire.frontend.account.solved-tests');
    }
}
