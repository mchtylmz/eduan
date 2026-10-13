<?php

namespace App\Livewire\Frontend\Tests;

use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Models\Exam;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PersonalResults extends Component
{
    use WithPagination;

    #[Locked]
    public Exam $exam;

    #[Computed]
    public function totals(): array
    {
        return auth()->check() ? examResultStats($this->exam, auth()->user()) : [];
    }

    #[Computed]
    public function attempts()
    {
        abort_unless(auth()->check(), 403);

        return $this->exam->results()->where('user_id', auth()->id())
            ->select(['id', 'exam_id', 'user_id', 'created_at', 'completed'])
            ->withCount([
                'details as answered_count',
                'details as correct_count' => fn ($query) => $query->where('correct', YesNoEnum::YES),
                'details as incorrect_count' => fn ($query) => $query->where('correct', YesNoEnum::NO),
            ])->latest('id')->paginate(15, ['*'], 'attemptsPage');
    }

    public function render()
    {
        abort_unless(Exam::whereKey($this->exam->id)->where('status', StatusEnum::ACTIVE)->exists(), 404);

        return view('livewire.frontend.tests.personal-results');
    }
}
