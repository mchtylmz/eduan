<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Jobs\CalculateLeagueResult;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('backend.home.index', [
            'title' => __('Anasayfa')
        ]);
    }
}
