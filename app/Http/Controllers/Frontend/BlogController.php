<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index()
    {
        return view('frontend.blogs.index', [
            'title' => __('Blog'),
        ]);
    }

    public function detail(Blog $blog)
    {
        abort_unless(StatusEnum::ACTIVE->is($blog->status) && $blog->published_at?->lte(now()), 404);
        incrementIf(true, $blog);

        return view('frontend.blogs.detail', [
            'title' => $blog->title,
            'blog' => $blog,
        ]);
    }
}
