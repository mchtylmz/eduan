<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index()
    {
        return view('backend.season.index', [
            'title' => __('Dönemler')
        ]);
    }
}
