<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $page = cache()->remember(
            'homepage',
            60 * 60 * 24 * 30,
            fn() => Page::where('menu', PageMenuEnum::HOME)->first()
        );

        return view('frontend.home.index', [
            'title' => $page->title ?? __('Anasayfa'),
            'page' => $page
        ]);
    }

    public function search()
    {
        return view('frontend.home.search', [
            'title' => __('Arama Sonucu')
        ]);
    }

    public function page(Page $page)
    {
        if (PageTypeEnum::SYSTEM->is($page->type)) {
            abort(404);
        }

        if (PageMenuEnum::FOOTER->isNot($page->menu)) {
            abort(404);
        }

        if (StatusEnum::ACTIVE->isNot($page->status)) {
            abort(404);
        }

        if (!empty(trim($page->link)) && str_starts_with($page->link, 'http')) {
            return redirect()->to($page->link);
        }

        return view('frontend.home.page', [
            'title' => $page->title,
            'page' => $page
        ]);
    }
}
