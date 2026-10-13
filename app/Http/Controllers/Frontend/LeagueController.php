<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\League;

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
            }),
        ]);
    }

    public function detail(League $league)
    {
        if (settings()->leagueStatus != 'active') {
            return redirect()->route('frontend.home');
        }

        return view('frontend.leagues.index', [
            'title' => __('Lig Sonuçları').($league->start_at && $league->end_at
                ? ' | '.dateFormat($league->start_at, 'd M Y').' – '.dateFormat($league->end_at, 'd M Y')
                : ''),
            'league' => $league,
        ]);
    }
}
