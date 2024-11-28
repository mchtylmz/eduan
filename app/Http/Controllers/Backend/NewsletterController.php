<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index()
    {
        return view('backend.newsletters.index', [
            'title' => __('Bilgilendirme Aboneleri')
        ]);
    }

    public function send()
    {
        return view('backend.newsletters.send', [
            'title' => __('Bilgilendirme Gönder')
        ]);
    }
}
