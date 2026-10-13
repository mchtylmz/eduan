<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\StatusEnum;
use App\Enums\VisibilityEnum;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Topic;

class TestController extends Controller
{
    public function index(?Topic $topic = null)
    {
        if ($topic?->exists) {
            abort_unless(StatusEnum::ACTIVE->is($topic->status) && $topic->lesson?->status === StatusEnum::ACTIVE, 404);
        }
        incrementIf(! empty($topic), $topic);

        return view('frontend.tests.index', [
            'title' => $topic?->title ? $topic->title.' | '.__('Testler') : __('Testler'),
            'topic' => $topic,
        ]);
    }

    public function detail(Exam $exam)
    {
        abort_unless(StatusEnum::ACTIVE->is($exam->status), 404);
        incrementIf(true, $exam);

        return view('frontend.tests.detail', [
            'title' => $exam->name,
            'exam' => $exam,
        ]);
    }

    public function start(Exam $exam)
    {
        if (! request()->user()) {
            return redirect()->route('login');
        }
        abort_unless(StatusEnum::ACTIVE->is($exam->status), 404);
        abort_unless(VisibilityEnum::LOGGED->is($exam->visibility) || request()->user()->can('exams:solve'), 403);

        return view('frontend.tests.start', [
            'title' => $exam->name,
            'exam' => $exam,
        ]);
    }

    public function solutions(Exam $exam)
    {
        if (! request()->user()) {
            return redirect()->route('login');
        }

        return view('frontend.tests.solutions', [
            'title' => $exam->name,
            'exam' => $exam,
            'results' => $exam->results()->where('user_id', request()->user()->id)
                ->with(['details.question', 'details.answer'])->orderBy('id')->paginate(5)->withQueryString(),
        ]);
    }
}
