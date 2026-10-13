<?php

namespace App\Http\Controllers\Backend;

use App\Enums\YesNoEnum;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Language;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function getData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('contacts:view'), 403);
        $sorts = ['id' => 'contacts.id', 'language' => Language::select('name')->whereColumn('languages.code', 'contacts.locale'), 'name' => 'contacts.name', 'email' => 'contacts.email', 'phone' => 'contacts.phone', 'school_name' => 'contacts.school_name', 'created_at' => 'contacts.created_at', 'has_read' => 'contacts.has_read'];
        $parameters = ServerTable::parameters($request, $sorts, 'created_at', ['locale' => ['nullable', 'string', 'exists:languages,code'], 'has_read' => ['sometimes', 'array', 'max:2'], 'has_read.*' => ['integer', 'in:0,1', 'distinct']]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Contact::query()->with('language');
        if (! empty($parameters['locale'])) {
            $query->where('locale', $parameters['locale']);
        }
        if (! empty($parameters['has_read'])) {
            $query->whereIn('has_read', $parameters['has_read']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(function ($query) use ($search) {
                $query->where('contacts.name', 'like', $search)
                    ->orWhere('contacts.email', 'like', $search)
                    ->orWhere('contacts.phone', 'like', $search)
                    ->orWhere('contacts.school_name', 'like', $search)
                    ->orWhere('contacts.has_read', 'like', $search);
            });
        }
        $data = ServerTable::page('contacts', $query, $parameters, $sorts, fn (Contact $record) => [
            'id' => $record->id,
            'name' => $record->name,
            'email' => $record->email,
            'phone' => $record->phone,
            'school_name' => $record->school_name,
            'created_at' => dateFormat($record->created_at, 'd M Y, H:i'),
            'has_read' => ['label' => YesNoEnum::YES->is($record->has_read) ? __('Okundu') : __('Okunmamış'), 'class' => YesNoEnum::YES->is($record->has_read) ? 'success' : 'warning'],
            'language' => $record->language?->name ?? $record->locale,
            'edit_url' => null,
            'view_url' => route('admin.contacts.detail', $record),
            'can_delete' => YesNoEnum::YES->is($record->has_read),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.contacts.index', [
            'title' => __('İletişim Mesajları'),
        ]);
    }

    public function detail(Contact $contact)
    {
        $contact->update(['has_read' => YesNoEnum::YES]);
        cache()->forget('data_countContactMessageNotRead');

        return view('backend.contacts.detail', [
            'title' => __('İletişim Mesajı'),
            'contact' => $contact,
        ]);
    }
}
