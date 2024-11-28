<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        return view('backend.lessons.index', [
            'title' => __('Dersler')
        ]);
    }

    public function create()
    {
        return view('backend.lessons.create', [
            'title' => __('Ders Ekle')
        ]);
    }

    public function edit(Lesson $lesson)
    {
        return view('backend.lessons.edit', [
            'title' => __('Ders Düzenle'),
            'lesson' => $lesson
        ]);
    }
}
