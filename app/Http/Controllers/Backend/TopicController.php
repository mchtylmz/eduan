<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index()
    {
        return view('backend.topics.index', [
            'title' => __('Konular')
        ]);
    }

    public function create()
    {
        return view('backend.topics.create', [
            'title' => __('Konu Ekle')
        ]);
    }

    public function edit(Topic $topic)
    {
        return view('backend.topics.edit', [
            'title' => __('Konu Düzenle'),
            'topic' => $topic
        ]);
    }
}
