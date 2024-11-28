<?php

namespace App\Livewire\Home;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class TestTable extends Component
{
    public bool $popularHits = false;
    public bool $popularResults = false;

    #[Computed]
    public function tests()
    {
        if ($this->popularHits) {
            return cache()->remember(
                'home_test-table_popularHits',
                60 * 60 * 2,
                fn() => Exam::with('language')->active()->orderByDesc('hits')->limit(6)->get()
            );
        }

        if ($this->popularResults) {
            return cache()->remember(
                'home_test-table_popularResults',
                60 * 60 * 2,
                fn() => ExamResult::selectRaw('*, COUNT(exam_id) as exams_count, COUNT(correct_count) as correct_count, COUNT(incorrect_count) as incorrect_count')
                    ->with('exam')
                    ->groupBy('exam_id')
                    ->orderByRaw('exams_count DESC')
                    ->get()
            );
        }

        return [];
    }

    public function render()
    {
        return view('livewire.backend.home.test-table');
    }
}
