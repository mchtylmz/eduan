<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PageMenuEnum;
use App\Enums\PageTypeEnum;
use App\Enums\StatusEnum;
use App\Helpers\FrontendCache;
use App\Helpers\SeoSitemap;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Question;

class HomeController extends Controller
{
    public function index()
    {
        $page = FrontendCache::remember(
            'pages', ['homepage'],
            fn () => Page::where('menu', PageMenuEnum::HOME)->first()
        );

        return view('frontend.home.index', [
            'title' => $page->title ?? __('Anasayfa'),
            'page' => $page,
        ]);
    }

    public function search()
    {
        return view('frontend.home.search', [
            'title' => __('Arama Sonucu'),
        ]);
    }

    public function aiSolution(Question $question)
    {
        abort_if(
            request()->user()->cannot('ai:solution'),
            403
        );

        return view('frontend.home.ai-solution', [
            'title' => __('Yapay Zeka Soru Cevaplama'),
            'question' => $question,
        ]);
    }

    public function page(Page $page)
    {
        if (PageTypeEnum::SYSTEM->is($page->type)) {
            return redirect()->to('/');
        }

        if (PageMenuEnum::FOOTER->isNot($page->menu)) {
            abort(404);
        }

        if (StatusEnum::ACTIVE->isNot($page->status)) {
            abort(404);
        }

        if (! empty(trim($page->link)) && str_starts_with($page->link, 'http')) {
            return redirect()->to($page->link);
        }

        return view('frontend.home.page', [
            'title' => $page->title,
            'page' => $page,
        ]);
    }

    public function sitemap()
    {
        return response()
            ->view('frontend.home.sitemap', ['entries' => SeoSitemap::entries()])
            ->header('Content-Type', 'text/xml');

    }
}
