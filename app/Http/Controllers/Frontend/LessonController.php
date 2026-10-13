<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Lesson;

class LessonController extends Controller
{
    public function index()
    {
        return view('frontend.lessons.index', [
            'title' => __('Dersler'),
        ]);
    }

    public function topics(Lesson $lesson)
    {
        abort_unless(StatusEnum::ACTIVE->is($lesson->status), 404);
        incrementIf(true, $lesson);

        return view('frontend.lessons.topics', [
            'title' => $lesson->name,
            'lesson' => $lesson,
        ]);
    }
}
