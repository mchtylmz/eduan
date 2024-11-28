<?php

namespace App\Livewire\Home;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Lesson;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class LessonTable extends Component
{
    #[Computed]
    public function lessons()
    {
        return cache()->remember(
            'home_lesson-table',
            60 * 60 * 2,
            fn() => Lesson::active()->orderByDesc('hits')->limit(6)->get()
        );
    }

    public function render()
    {
        return view('livewire.backend.home.lesson-table');
    }
}
