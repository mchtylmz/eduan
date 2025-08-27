<?php

namespace App\Livewire\Frontend\Account;

use App\Actions\Exams\UpdateExamFavoriteForUserAction;
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
        return Exam::with(['language', 'userResults'])
            ->whereHas('userResults', fn ($query) => $query->where('user_id', auth()->id()))
            ->when(
                $this->showNotComplete,
                fn ($query) => $query->whereHas('userResults', fn ($query) => $query->where('completed', YesNoEnum::NO))
            )
            ->active()
            ->paginate($this->paginate);
    }

    public function render()
    {
        return view('livewire.frontend.account.solved-tests');
    }
}
