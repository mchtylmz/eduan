<?php

namespace App\Http\Controllers\Backend;

use App\Enums\YesNoEnum;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return view('backend.contacts.index', [
            'title' => __('İletişim Mesajları')
        ]);
    }

    public function detail(Contact $contact)
    {
        $contact->update(['has_read' => YesNoEnum::YES]);
        cache()->forget('data_countContactMessageNotRead');

        return view('backend.contacts.detail', [
            'title' => __('İletişim Mesajı'),
            'contact' => $contact
        ]);
    }
}
