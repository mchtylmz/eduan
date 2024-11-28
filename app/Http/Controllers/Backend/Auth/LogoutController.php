<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    public function index()
    {
        LogoutAction::run();

        return redirect()->route('login');
    }
}
