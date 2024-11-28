<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Actions\Auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Auth\LoginRequest;

class LoginController extends Controller
{
    public function index()
    {
        return view('backend.auth.login', [
            'title' => __('Giriş Yap')
        ]);
    }
}
