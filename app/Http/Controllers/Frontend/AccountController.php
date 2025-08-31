<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Auth\LogoutAction;
use App\Enums\YesNoEnum;
use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\Test;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('frontend.account.index', [
            'title' => __('Hesabım')
        ]);
    }

    public function favorite()
    {
        return view('frontend.account.favorite', [
            'title' => __('Favori Testlerim')
        ]);
    }

    public function stats()
    {
        return view('frontend.account.stats', [
            'title' => __('İstatistikler')
        ]);
    }

    public function solved()
    {
        return view('frontend.account.solved', [
            'title' => __('Testlerim')
        ]);
    }

    public function solvedNotCompleteTests()
    {
        return view('frontend.account.solved-not-complete', [
            'title' => __('Çözmediğim Testler')
        ]);
    }

    public function results()
    {
        return view('frontend.account.results', [
            'title' => __('Sınavlarım')
        ]);
    }

    public function result(Test $test)
    {
        return view('frontend.account.result', [
            'title' => __('Sınav Sonucu') . ' ' . $test->name,
            'test' => $test,
            'results' => $test->userResultsWithoutGroupBy()
                ->with(['details', 'test'])
                ->where('completed', YesNoEnum::YES)
                ->orderBy('id')
                ->get()
        ]);
    }

    public function unsubscribe(Newsletter $newsletter)
    {
        $newsletter->delete();

        return view('frontend.account.unsubscribe', [
            'title' => __('Abonelikten Çık')
        ]);
    }

    public function logout()
    {
        LogoutAction::run();

        return redirect()->route('frontend.home');
    }
}
