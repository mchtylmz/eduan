<?php

namespace App\Http\Controllers\Frontend;

use App\Filters\LessonFilter;
use App\Filters\TopicFilter;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        return view('frontend.lessons.index', [
            'title' => __('Dersler')
        ]);
    }

    public function topics(Lesson $lesson)
    {
        incrementIf(true, $lesson);

        return view('frontend.lessons.topics', [
            'title' => $lesson->name,
            'lesson' => $lesson,
        ]);
    }
}
