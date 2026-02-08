<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function index()
    {
        if (settings()->leagueStatus != 'active') {
            return redirect()->route('frontend.home');
        }

        return view('frontend.leagues.index', [
            'title' => __('Ligler'),
            'league' => cache()->remember('leagues_first', now()->addHours(12), function () {
                return League::first();
            })
        ]);
    }

    public function detail(League $league)
    {
        if (settings()->leagueStatus != 'active') {
            return redirect()->route('frontend.home');
        }

        return view('frontend.leagues.index', [
            'title' => $league->name,
            'league' => $league
        ]);
    }
}
