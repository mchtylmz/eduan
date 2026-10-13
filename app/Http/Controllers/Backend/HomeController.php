<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\DashboardStats;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('backend.home.index', [
            'title' => __('Anasayfa'),
            'statistics' => DashboardStats::get(),
        ]);
    }
}
