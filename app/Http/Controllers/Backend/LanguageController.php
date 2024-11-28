<?php

namespace App\Http\Controllers\Backend;

use App\Models\Language;

class LanguageController
{
    public function index()
    {
        return view('backend.languages.index', [
            'title' => __('Diller & Çeviriler')
        ]);
    }

    public function create()
    {
        return view('backend.languages.create', [
            'title' => __('Dil Ekle')
        ]);
    }

    public function edit(Language $language)
    {
        return view('backend.languages.edit', [
            'title' => __('Dil Düzenle'),
            'language' => $language
        ]);
    }

    public function translate(Language $language)
    {
        return view('backend.languages.translate', [
            'title' => __('Çevirileri Düzenle'),
            'language' => $language
        ]);
    }
}
