<?php

namespace App\Livewire\Frontend\Account;

use App\Actions\Exams\UpdateExamFavoriteForUserAction;
use App\Enums\ReviewVisibilityEnum;
use App\Enums\YesNoEnum;
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
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public bool $showPaginate = true;
    public bool $showNotComplete = false;
    public int $paginate = 21;

    #[Computed]
    public function tests()
    {
        if ($this->showNotComplete) {
            return Exam::with(['language', 'userResults'])
                ->withCount([
                    'questions' => fn($query) => $query->active(),
                ])
                ->whereNotIn('id', ExamResult::where('user_id', auth()->id())->pluck('exam_id'))
                ->active()
                ->paginate($this->paginate);
        }

        return Exam::with(['language', 'userResults'])
            ->whereHas('userResults', fn ($query) => $query->where('user_id', auth()->id()))
            ->active()
            ->paginate($this->paginate);
    }

    public function render()
    {
        return view('livewire.frontend.account.solved-tests');
    }
}
