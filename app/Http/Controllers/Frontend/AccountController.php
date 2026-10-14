<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Auth\LogoutAction;
use App\Actions\Study\AuthorizeStudyAccess;
use App\Enums\YesNoEnum;
use App\Helpers\StudyCalendar;
use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\Test;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('frontend.account.index', [
            'title' => __('account.title'),
        ]);
    }

    public function study(Request $request)
    {
        AuthorizeStudyAccess::run($request->user());
        $currentWeek = StudyCalendar::bounds(now())['start'];
        $request->validate(['week' => 'sometimes|required|date_format:Y-m-d']);
        $bounds = StudyCalendar::bounds($request->query('week', $currentWeek->toDateString()));
        abort_unless(! $bounds['start']->greaterThan($currentWeek), 422);

        return response()->view('frontend.study.index', [
            'title' => __('study.my_study'), 'bounds' => $bounds, 'currentWeek' => $currentWeek,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function favorite()
    {
        return view('frontend.account.favorite', [
            'title' => __('account.favorites'),
        ]);
    }

    public function stats()
    {
        return view('frontend.account.stats', [
            'title' => __('account.stats'),
        ]);
    }

    public function solved()
    {
        return view('frontend.account.solved', [
            'title' => __('account.tests'),
        ]);
    }

    public function solvedNotCompleteTests()
    {
        return view('frontend.account.solved-not-complete', [
            'title' => __('account.not_completed'),
        ]);
    }

    public function results()
    {
        return view('frontend.account.results', [
            'title' => __('account.exams'),
        ]);
    }

    public function myLeagues()
    {
        return view('frontend.account.my-leagues', [
            'title' => __('account.leagues'),
        ]);
    }

    public function result(Test $test)
    {
        return view('frontend.account.result', [
            'title' => __('account.exam_result').' '.$test->name,
            'test' => $test,
            'sections' => $test->sections()->parentIsZero()->with('parents.metarelation')->get(),
            'results' => $test->userResultsWithoutGroupBy()
                ->with(['details.question', 'details.answer'])
                ->where('completed', YesNoEnum::YES)
                ->orderBy('id')
                ->paginate(5)->withQueryString(),
        ]);
    }

    public function unsubscribe(Newsletter $newsletter)
    {
        $newsletter->delete();

        return view('frontend.account.unsubscribe', [
            'title' => __('Abonelikten Çık'),
        ]);
    }

    public function logout()
    {
        LogoutAction::run();

        return redirect()->route('frontend.home');
    }
}
