<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return view('backend.faqs.index', [
            'title' => __('Sıkça Sorulan Sorular')
        ]);
    }

    public function create()
    {
        return view('backend.faqs.create', [
            'title' => __('Sıkça Sorulan Soru Ekle')
        ]);
    }

    public function edit(Faq $faq)
    {
        return view('backend.faqs.edit', [
            'title' => __('Sıkça Sorulan Soru Düzenle'),
            'faq' => $faq
        ]);
    }
}
