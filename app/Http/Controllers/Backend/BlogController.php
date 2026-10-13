<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Language;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('blogs:view'), 403);
        $sorts = ['id' => 'blogs.id', 'language' => Language::select('name')->whereColumn('languages.code', 'blogs.locale'), 'title' => 'blogs.title', 'hits' => 'blogs.hits', 'status' => 'blogs.status', 'published_at' => 'blogs.published_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'published_at', ['locale' => ['nullable', 'string', 'exists:languages,code']]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Blog::query()->with('language');
        if (! empty($parameters['locale'])) {
            $query->where('locale', $parameters['locale']);
        }
        if (! empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->where('blogs.title', 'like', $search);
                $query->orWhere('blogs.status', 'like', $search);
            });
        }
        $data = ServerTable::page('blogs', $query, $parameters, $sorts, fn (Blog $record) => [
            'id' => $record->id,
            'title' => $record->title,
            'hits' => $record->hits,
            'status' => ['label' => $record->status->name(), 'class' => $record->status->class()],
            'published_at' => dateFormat($record->published_at, 'd M Y, H:i'),
            'language' => $record->language?->name ?? $record->locale,
            'edit_url' => $actor->can('blogs:update') ? route('admin.blogs.edit', $record) : null,
            'can_delete' => $actor->can('blogs:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.blogs.index', [
            'title' => __('Bloglar'),
        ]);
    }

    public function create()
    {
        return view('backend.blogs.create', [
            'title' => __('Blog Ekle'),
        ]);
    }

    public function edit(Blog $blog)
    {
        return view('backend.blogs.edit', [
            'title' => __('Blog Düzenle'),
            'blog' => $blog,
        ]);
    }
}
