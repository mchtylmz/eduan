<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Topic;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Topic $topic = null)
    {
        incrementIf(!empty($topic), $topic);

        return view('frontend.tests.index', [
            'title' => __('Testler'),
            'topic' => $topic,
        ]);
    }

    public function detail(Exam $exam)
    {
        incrementIf(true, $exam);

        return view('frontend.tests.detail', [
            'title' => $exam->name,
            'exam' => $exam,
        ]);
    }

    public function start(Exam $exam)
    {
        return view('frontend.tests.start', [
            'title' => $exam->name,
            'exam' => $exam,
        ]);
    }

    public function solutions(Exam $exam)
    {
        return view('frontend.tests.solutions', [
            'title' => $exam->name,
            'exam' => $exam,
            'results' => $exam->userResults()->with('details')->orderBy('id')->get()
        ]);
    }
}
