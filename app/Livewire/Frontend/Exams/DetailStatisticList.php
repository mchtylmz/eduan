<?php

namespace App\Livewire\Frontend\Exams;

use App\Enums\TestSectionTypeEnum;
use App\Models\Test;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DetailStatisticList extends Component
{
    #[Locked]
    public Test $test;

    #[Computed]
    public function sectionsWithQuestionsCount()
    {
        return $this->test->sections()
            ->parentIsZero()
            ->withCount(['parents as questions_count' => function ($query) {
                $query->where('type', TestSectionTypeEnum::QUESTION);
            }])
            ->get();
    }

    public function render()
    {
        return view('livewire.frontend.exams.detail-statistic-list');
    }
}
