<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\StatusEnum;
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
        if (StatusEnum::PASSIVE->is($test->status) || request()->user()->cannot('tests:solve')) {
            return redirect()->route('frontend.exam.detail', $test->code);
        }

        return view('frontend.exams.start', [
            'title' => $test->name,
            'test' => $test,
        ]);
    }
}
