<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index()
    {
        return view('backend.stats.index', [
            'title' => __('İstatistik Raporu')
        ]);
    }
}
