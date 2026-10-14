<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Communications\AuthorizeCommunication;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\CommunicationDelivery;
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

    public function history(Request $request)
    {
        AuthorizeCommunication::run();
        $sorts = ['created_at' => 'communication_deliveries.created_at', 'title' => 'communication_deliveries.title',
            'channel' => 'communication_deliveries.channel', 'status' => 'communication_deliveries.status'];
        $p = ServerTable::parameters($request, $sorts, 'created_at', [
            'channel' => 'nullable|in:email,push', 'delivery_status' => 'nullable|in:pending,scheduled,sending,sent,failed,skipped',
        ]);
        $p['order'] = $request->has('order') ? $p['order'] : 'desc';
        $query = CommunicationDelivery::query()->leftJoin('users as recipient', 'recipient.id', '=', 'communication_deliveries.user_id');
        if (! auth()->user()->can('user-type:admin')) {
            $query->where('communication_deliveries.actor_id', auth()->id());
        }
        if (! empty($p['channel'])) {
            $query->where('communication_deliveries.channel', $p['channel']);
        }
        if (! empty($p['delivery_status'])) {
            $query->where('communication_deliveries.status', $p['delivery_status']);
        }
        if ($p['search'] !== '') {
            $search = '%'.$p['search'].'%';
            $query->where(fn ($q) => $q->where('communication_deliveries.title', 'like', $search)
                ->orWhere('recipient.name', 'like', $search)->orWhere('recipient.surname', 'like', $search));
        }
        $total = (clone $query)->count();
        $rows = $query->orderBy($sorts[$p['sort']], $p['order'])->orderByDesc('communication_deliveries.id')
            ->offset($p['offset'])->limit($p['limit'])->get(['communication_deliveries.id', 'communication_deliveries.channel',
                'communication_deliveries.title', 'communication_deliveries.status', 'communication_deliveries.created_at',
                'recipient.name as recipient_name', 'recipient.surname as recipient_surname'])->map(fn ($row) => [
                    'id' => $row->id, 'recipient' => trim($row->recipient_name.' '.$row->recipient_surname) ?: '—',
                    'channel' => __('notifications.'.$row->channel), 'title' => $row->title,
                    'status' => __('notifications.status_'.$row->status), 'created_at' => dateFormat($row->created_at),
                ])->all();

        // Delivery states change in the worker; return current status without a stale shared cache.
        return response()->json(compact('total', 'rows'))->header('Cache-Control', 'private, no-store');
    }
}
