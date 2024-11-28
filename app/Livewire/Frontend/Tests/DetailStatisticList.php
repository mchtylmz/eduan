<?php

namespace App\Livewire\Frontend\Tests;

use App\Models\Exam;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class DetailStatisticList extends Component
{
    public Exam $exam;

    public function lessons()
    {
        return Cache::remember(
            implode('_', ['lessons', $this->exam->id, auth()->id()]),
            60 * 60 * 24 * 365,
            function () {
                return $this->exam->questions()
                    ->active()
                    ->with('lesson')
                    ->selectRaw('count(questions.id) as questions_count, questions.lesson_id')
                    ->groupBy('questions.lesson_id')
                    ->get();
            }
        );
    }

    public function topics()
    {
        return Cache::remember(
            implode('_', ['topics', $this->exam->id, auth()->id()]),
            60 * 60 * 24 * 365,
            function () {
                return $this->exam->questions()
                    ->active()
                    ->with('topic')
                    ->selectRaw('count(questions.id) as questions_count, questions.topic_id')
                    ->groupBy('questions.topic_id')
                    ->get();
            }
        );
    }

    public function render()
    {
        return view('livewire.frontend.tests.detail-statistic-list');
    }
}
