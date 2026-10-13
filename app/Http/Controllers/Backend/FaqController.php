<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Language;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('pages:view'), 403);
        $sorts = ['id' => 'faqs.id', 'language' => Language::select('name')->whereColumn('languages.code', 'faqs.locale'), 'title' => 'faqs.title', 'sort' => 'faqs.sort', 'status' => 'faqs.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', ['locale' => ['nullable', 'string', 'exists:languages,code']]);
        $query = Faq::query()->with('language');
        if (! empty($parameters['locale'])) {
            $query->where('locale', $parameters['locale']);
        }
        if (! empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->where('faqs.title', 'like', $search);
                $query->orWhere('faqs.status', 'like', $search);
            });
        }
        $data = ServerTable::page('faqs', $query, $parameters, $sorts, fn (Faq $record) => [
            'id' => $record->id,
            'title' => $record->title,
            'sort' => $record->sort,
            'status' => ['label' => $record->status->name(), 'class' => $record->status->class()],
            'language' => $record->language?->name ?? $record->locale,
            'edit_url' => $actor->can('pages:update') ? route('admin.pages.faqs.edit', $record) : null,
            'can_delete' => $actor->can('pages:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.faqs.index', [
            'title' => __('Sıkça Sorulan Sorular'),
        ]);
    }

    public function create()
    {
        return view('backend.faqs.create', [
            'title' => __('Sıkça Sorulan Soru Ekle'),
        ]);
    }

    public function edit(Faq $faq)
    {
        return view('backend.faqs.edit', [
            'title' => __('Sıkça Sorulan Soru Düzenle'),
            'faq' => $faq,
        ]);
    }
}
