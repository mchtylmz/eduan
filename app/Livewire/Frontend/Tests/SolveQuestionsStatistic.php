<?php

namespace App\Livewire\Frontend\Tests;

use App\Models\Exam;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class SolveQuestionsStatistic extends Component
{
    public Exam $exam;

    public function questionTimes()
    {
        return $this->exam->questions()->active()->select('time')->get();
    }

    public function render()
    {
        return view('livewire.frontend.tests.solve-questions-statistic');
    }
}
