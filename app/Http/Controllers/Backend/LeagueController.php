<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function index()
    {
        return view('backend.leagues.index', [
            'title' => __('Ligler / Lig Sonuçları')
        ]);
    }
}
