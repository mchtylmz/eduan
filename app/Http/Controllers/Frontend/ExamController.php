<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Test;
use App\Models\Topic;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        return view('frontend.exams.index', [
            'title' => __('Sınavlar')
        ]);
    }

    public function detail(Test $test)
    {
        incrementIf(true, $test);

        return view('frontend.exams.detail', [
            'title' => $test->name,
            'test' => $test,
        ]);
    }

    public function start(Test $test)
    {
        return view('frontend.exams.start', [
            'title' => $test->name,
            'test' => $test,
        ]);
    }
}
