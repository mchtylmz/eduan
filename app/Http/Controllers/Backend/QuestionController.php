<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        return view('backend.questions.index', [
            'title' => __('Soru Havuzu')
        ]);
    }

    public function create()
    {
        return view('backend.questions.create', [
            'title' => __('Soru Ekle')
        ]);
    }

    public function edit(Question $question)
    {
        return view('backend.questions.edit', [
            'title' => __('Soru Düzenle'),
            'question' => $question
        ]);
    }
}
