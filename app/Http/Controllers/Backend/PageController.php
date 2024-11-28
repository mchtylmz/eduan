<?php

namespace App\Http\Controllers\Backend;

use App\Enums\PageTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        return view('backend.pages.home', [
            'title' => __('Anasayfa')
        ]);
    }

    public function all()
    {
        return view('backend.pages.all', [
            'title' => __('Diğer Sayfalar')
        ]);
    }

    public function create()
    {
        return view('backend.pages.create', [
            'title' => __('Sayfa Ekle')
        ]);
    }

    public function edit(Page $page)
    {
        if (PageTypeEnum::SYSTEM->is($page->type)) {
            abort(400);
        }

        return view('backend.pages.edit', [
            'title' => __('Sayfa Düzenle'),
            'page' => $page
        ]);
    }
}
