<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AnswerAI;
use App\Models\Exam;
use App\Models\LatexImage;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected array $aiTabs = [
        'answer',
        'usages',
        'votes',
        'edit',
    ];

    public function index()
    {
        return view('backend.ai.index', [
            'title' => __('Yapay Zeka')
        ]);
    }

    public function images()
    {
        return view('backend.ai.images', [
            'title' => __('Latex için Üretilen Görseller')
        ]);
    }

    public function imageForm(LatexImage $latexImage)
    {
        return view('backend.ai.image-form', [
            'title' => __('Latex Görseli Güncelle'),
            'latexImage' => $latexImage
        ]);
    }

    public function edit(AnswerAI $answerAI)
    {
        if (!request()->user()->canany(['ai:view'])) {
            abort(403, __('Yanıt güncellenemez, yetkiniz bulunmuyor!'));
        }

        $activeTab = in_array(request()->input('tab'), $this->aiTabs) ? request()->input('tab') : 'answer';

        return view('backend.ai.edit', [
            'title' => __('Yapay Zeka Yanıtı'),
            'answerAI' => $answerAI,
            'activeTab' => $activeTab
        ]);
    }

}
