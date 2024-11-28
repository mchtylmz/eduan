<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use App\Models\Newsletter;
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


    public function solved()
    {
        return view('frontend.account.solved', [
            'title' => __('Testlerim')
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
