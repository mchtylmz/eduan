<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        return view('backend.blogs.index', [
            'title' => __('Bloglar')
        ]);
    }

    public function create()
    {
        return view('backend.blogs.create', [
            'title' => __('Blog Ekle')
        ]);
    }

    public function edit(Blog $blog)
    {
        return view('backend.blogs.edit', [
            'title' => __('Blog Düzenle'),
            'blog' => $blog
        ]);
    }
}
