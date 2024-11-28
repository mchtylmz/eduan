<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Livewire\Events;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        return view('frontend.blogs.index', [
            'title' => __('Blog')
        ]);
    }

    public function detail(Blog $blog)
    {
        incrementIf(true, $blog);

        return view('frontend.blogs.detail', [
            'title' => $blog->title,
            'blog' => $blog
        ]);
    }
}
