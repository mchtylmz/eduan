<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\LearningTable;
use App\Helpers\ServerTable;
use App\Helpers\TableCache;
use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\LeagueResult;
use App\Models\User;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function getData(Request $request)
    {
        LearningTable::access('leagues');
        $sorts = ['id' => 'leagues.id', 'code' => 'leagues.code', 'label' => 'leagues.label', 'start_at' => 'leagues.start_at', 'end_at' => 'leagues.end_at', 'calculated_at' => 'leagues.calculated_at'];
        $p = ServerTable::parameters($request, $sorts, 'id', ['start_at' => ['nullable', 'date_format:Y-m-d'], 'end_at' => ['nullable', 'date_format:Y-m-d']]);
        if (! $request->has('order')) {
            $p['order'] = 'desc';
        }
        $q = League::query();
        foreach (['start_at', 'end_at'] as $date) {
            if (! empty($p[$date])) {
                $q->whereDate($date, $p[$date]);
            }
        }
        if ($p['search'] !== '') {
            $q->where(fn ($q) => $q->where('code', 'like', '%'.$p['search'].'%')->orWhere('label', 'like', '%'.$p['search'].'%'));
        }

        return response()->json(ServerTable::page('leagues', $q, $p, $sorts, fn ($r) => [
            'id' => $r->id, 'code' => $r->code, 'label' => in_array($r->label, ['tests_result_details', 'tests_results'], true) ? __('Sınav Sonuçları') : __('Test Sonuçları'),
            'start_at' => dateFormat($r->start_at, 'd M Y H:i'), 'end_at' => dateFormat($r->end_at, 'd M Y H:i'),
            'calculated_at' => $r->calculated_at ? dateFormat($r->calculated_at, 'd/m/Y H:i') : '-', 'can_delete' => auth()->user()->can('leagues:delete'),
            'calculated_through_at' => $r->calculated_through_at ? dateFormat($r->calculated_through_at, 'd/m/Y H:i') : '-',
            'status' => ['label' => __('leagues.status_'.$r->calculationStatus()), 'class' => match ($r->calculationStatus()) {
                'live' => 'success', 'scheduled', 'finalizing' => 'warning', default => 'secondary',
            }],
            'can_recalculate' => $r->is_live && $r->closed_at !== null && auth()->user()->can('leagues:run'),
        ], cache: false))->header('Cache-Control', 'private, no-store');
    }

    public function getResultsData(Request $request)
    {
        LearningTable::access('leagues');
        $sorts = ['id' => 'league_results.id', 'name' => User::select('name')->whereColumn('users.id', 'league_results.user_id'),
            'surname' => User::select('surname')->whereColumn('users.id', 'league_results.user_id'),
            'total_correct' => 'league_results.total_correct', 'total_incorrect' => 'league_results.total_incorrect', 'league_number' => 'league_results.league_number',
            'position' => 'league_results.position', 'total_score' => 'league_results.total_score'];
        $p = ServerTable::parameters($request, $sorts, 'league_number', ['league_id' => ['required', 'integer', 'exists:leagues,id'],
            'league_number' => ['nullable', 'integer', 'between:1,7'], 'users' => ['sometimes', 'array', 'max:100'], 'users.*' => ['integer', 'distinct', 'exists:users,id']]);
        League::findOrFail($p['league_id']);
        $students = LearningTable::studentIds();
        $q = LeagueResult::with('user:id,name,surname')->where('league_id', $p['league_id'])->when($students !== null, fn ($q) => $q->whereIn('user_id', $students));
        if (! empty($p['league_number'])) {
            $q->where('league_number', $p['league_number']);
        }
        if (! empty($p['users'])) {
            $q->whereIn('user_id', $p['users']);
        }
        if ($p['search'] !== '') {
            $q->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$p['search'].'%')->orWhere('surname', 'like', '%'.$p['search'].'%'));
        }

        // Preserve the existing league number, then position ordering.
        return response()->json(TableCache::remember('league-results', $p, function () use ($q, $p, $sorts) {
            $total = (clone $q)->count();
            $rows = $q->reorder()->orderBy($sorts[$p['sort']], $p['order'])->orderBy('position')->orderBy('id')->offset($p['offset'])->limit($p['limit'])->get()
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->user?->name ?? '-', 'surname' => $r->user?->surname ?? '-', 'total_correct' => $r->total_correct,
                    'total_incorrect' => $r->total_incorrect, 'league_number' => $r->league_number, 'position' => $r->position, 'total_score' => $r->total_score])->all();

            return ['total' => $total, 'rows' => $rows];
        }, [$students]))->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.leagues.index', [
            'title' => __('Ligler / Lig Sonuçları'),
        ]);
    }
}
