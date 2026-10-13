<?php

namespace App\Livewire\Frontend\Tests;

use App\Helpers\FrontendCache;
use App\Models\Exam;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DetailStatisticList extends Component
{
    #[Locked]
    public Exam $exam;

    #[Computed]
    public function summary(): array
    {
        return FrontendCache::remember(
            'tests', ['detail-summary' => $this->exam->id],
            function () {
                $totals = $this->exam->questions()->active()
                    ->selectRaw('COUNT(questions.id) as questions_count, COALESCE(SUM(questions.time), 0) as seconds')->first();

                return [
                    'questions_count' => (int) $totals->questions_count,
                    'seconds' => (int) $totals->seconds,
                    'lessons' => $this->exam->questions()->active()->with('lesson:id,name')
                        ->selectRaw('count(questions.id) as questions_count, questions.lesson_id')->groupBy('questions.lesson_id')->get(),
                    'topics' => $this->exam->questions()->active()->with('topic:id,title')
                        ->selectRaw('count(questions.id) as questions_count, questions.topic_id')->groupBy('questions.topic_id')->get(),
                ];
            }
        );
    }

    public function lessons()
    {
        return $this->summary()['lessons'];
    }

    public function topics()
    {
        return $this->summary()['topics'];
    }

    public function render()
    {
        return view('livewire.frontend.tests.detail-statistic-list');
    }
}
