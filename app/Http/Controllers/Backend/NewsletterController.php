<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('newsletter:view'), 403);
        $sorts = ['id' => 'newsletters.id', 'email' => 'newsletters.email', 'ip' => 'newsletters.ip', 'created_at' => 'newsletters.created_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', []);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Newsletter::query();
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->where('newsletters.email', 'like', $search);
                $query->orWhere('newsletters.ip', 'like', $search);
            });
        }
        $data = ServerTable::page('newsletter', $query, $parameters, $sorts, fn (Newsletter $record) => [
            'id' => $record->id,
            'email' => $record->email,
            'ip' => $record->ip,
            'created_at' => dateFormat($record->created_at, 'd M Y, H:i'),
            'edit_url' => null,
            'can_delete' => $actor->can('newsletter:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.newsletters.index', [
            'title' => __('Bilgilendirme Aboneleri'),
        ]);
    }

    public function send()
    {
        return view('backend.newsletters.send', [
            'title' => __('Bilgilendirme Gönder'),
        ]);
    }
}
