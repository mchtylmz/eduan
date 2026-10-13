<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PageTypeEnum;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('pages:view'), 403);
        $sorts = ['id' => 'pages.id', 'title' => 'title->'.app()->getLocale(), 'sort' => 'pages.sort', 'link' => 'link->'.app()->getLocale(), 'status' => 'pages.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', []);
        $query = Page::query();
        if (! empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->where('title->'.app()->getLocale(), 'like', $search);
                $query->orWhere('pages.status', 'like', $search);
            });
        }
        $data = ServerTable::page('pages', $query, $parameters, $sorts, fn (Page $record) => [
            'id' => $record->id,
            'title' => $record->title,
            'sort' => $record->sort,
            'link' => $record->link ?: '-',
            'status' => ['label' => $record->status->name(), 'class' => $record->status->class()],
            'edit_url' => $actor->can('pages:update') && ! PageTypeEnum::SYSTEM->is($record->type) ? route('admin.pages.edit', $record) : null,
            'can_delete' => $actor->can('pages:delete') && ! PageTypeEnum::SYSTEM->is($record->type),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function home()
    {
        return view('backend.pages.home', [
            'title' => __('Anasayfa'),
        ]);
    }

    public function all()
    {
        return view('backend.pages.all', [
            'title' => __('Diğer Sayfalar'),
        ]);
    }

    public function create()
    {
        return view('backend.pages.create', [
            'title' => __('Sayfa Ekle'),
        ]);
    }

    public function edit(Page $page)
    {
        if (PageTypeEnum::SYSTEM->is($page->type)) {
            abort(400);
        }

        return view('backend.pages.edit', [
            'title' => __('Sayfa Düzenle'),
            'page' => $page,
        ]);
    }
}
