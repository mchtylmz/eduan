<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    protected array $tabs = [
        'stats',
        'corrects'
    ];

    public function index()
    {
        $activeTab = in_array(request()->input('tab'), $this->tabs) ? request()->input('tab') : 'stats';

        return view('backend.stats.index', [
            'title' => __('İstatistik Raporu'),
            'activeTab' => $activeTab
        ]);
    }
}
