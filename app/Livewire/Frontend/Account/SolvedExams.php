<?php

namespace App\Livewire\Frontend\Account;

use App\Enums\YesNoEnum;
use App\Models\Exam;
use App\Models\Test;
use App\Traits\CustomLivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy(isolate: true)]
class SolvedExams extends Component
{
    use WithPagination, CustomLivewireAlert;

    protected string $paginationTheme = 'bootstrap';

    public bool $showPaginate = true;
    public int $paginate = 21;

    #[Computed]
    public function exams()
    {
        return Test::with(['language', 'userResults'])
            ->whereHas('userResults', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('completed', YesNoEnum::YES);
            })
            ->withCount([
                'results' => fn($query) => $query->where('completed', YesNoEnum::YES)
            ])
            ->active()
            ->paginate($this->paginate);
    }

    public function render()
    {
        return view('livewire.frontend.account.solved-exams');
    }
}
