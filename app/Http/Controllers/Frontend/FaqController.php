<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PageMenuEnum;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $page = cache()->remember(
            'homepage',
            60 * 60 * 24 * 30,
            fn() => Page::where('menu', PageMenuEnum::HOME)->first()
        );

        return view('frontend.faqs.index', [
            'title' => __('Sıkça Sorulan Sorular'),
            'page' => $page,
        ]);
    }
}
