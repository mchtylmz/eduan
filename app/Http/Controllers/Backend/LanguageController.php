<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ServerTable;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController
{
    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('languages:view'), 403);
        $sorts = ['id' => 'languages.id', 'code' => 'languages.code', 'name' => 'languages.name', 'status' => 'languages.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', []);
        $query = Language::query();
        if (! empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->where('languages.code', 'like', $search);
                $query->orWhere('languages.name', 'like', $search);
                $query->orWhere('languages.status', 'like', $search);
            });
        }
        $data = ServerTable::page('languages', $query, $parameters, $sorts, fn (Language $record) => [
            'id' => $record->id,
            'code' => strtoupper($record->code),
            'name' => $record->name,
            'status' => ['label' => $record->status->name(), 'class' => $record->status->class()],
            'edit_url' => $actor->can('languages:update') ? route('admin.languages.edit', $record) : null,
            'can_delete' => $actor->can('languages:delete'),
            'translate_url' => $actor->can('languages:translate') ? route('admin.languages.translate', $record) : null,
            'is_default' => ['label' => settings()->defaultLocale === $record->code ? __('Evet') : __('Hayır'), 'class' => settings()->defaultLocale === $record->code ? 'success' : 'secondary'],
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.languages.index', [
            'title' => __('Diller & Çeviriler'),
        ]);
    }

    public function create()
    {
        return view('backend.languages.create', [
            'title' => __('Dil Ekle'),
        ]);
    }

    public function edit(Language $language)
    {
        return view('backend.languages.edit', [
            'title' => __('Dil Düzenle'),
            'language' => $language,
        ]);
    }

    public function translate(Language $language)
    {
        abort_unless(request()->user()?->can('languages:translate'), 403);

        return view('backend.languages.translate', [
            'title' => __('Çevirileri Düzenle'),
            'language' => $language,
        ]);
    }
}
