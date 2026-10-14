<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Communications\AuthorizeCommunication;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index()
    {
        AuthorizeCommunication::run();

        return response()->view('backend.communications.index', ['title' => __('notifications.communications')])->header('Cache-Control', 'private, no-store');
    }

    public function recipients(Request $request)
    {
        AuthorizeCommunication::run();
        $p = ServerTable::parameters($request, ['name' => 'name', 'surname' => 'surname'], 'name');
        $query = User::active();
        if (! auth()->user()->can('user-type:admin')) {
            $query->whereIn('users.id', auth()->user()->students()->select('users.id'));
        }
        if ($p['search'] !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$p['search'].'%')->orWhere('surname', 'like', '%'.$p['search'].'%')->orWhere('username', 'like', '%'.$p['search'].'%'));
        }
        $total = (clone $query)->count();
        $rows = $query->orderBy($p['sort'], $p['order'])->orderBy('id')->offset($p['offset'])->limit($p['limit'])->get(['id', 'name', 'surname', 'username'])->toArray();

        return response()->json(compact('total', 'rows'))->header('Cache-Control', 'private, no-store');
    }
}
