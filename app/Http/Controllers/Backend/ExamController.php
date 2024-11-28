<?php

namespace App\Http\Controllers\Backend;

use App\Enums\YesNoEnum;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamReview;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        return view('backend.exams.index', [
            'title' => __('Testler')
        ]);
    }

    public function create()
    {
        return view('backend.exams.create', [
            'title' => __('Test Oluştur')
        ]);
    }

    public function edit(Exam $exam)
    {
        return view('backend.exams.edit', [
            'title' => __('Test Düzenle'),
            'exam' => $exam
        ]);
    }

    public function results()
    {
        return view('backend.exams.results', [
            'title' => __('Test Sonuçları')
        ]);
    }

    public function result(Exam $exam)
    {
        return view('backend.exams.result', [
            'title' => __('Test Sonucu'),
            'exam' => $exam
        ]);
    }

    public function reviews()
    {
        return view('backend.exams.reviews', [
            'title' => __('Değerlendirmeler')
        ]);
    }

    public function review(Exam $exam)
    {
        return view('backend.exams.review', [
            'title' => __('Değerlendirmeler'),
            'exam' => $exam
        ]);
    }

    public function reviewDetail(ExamReview $review)
    {
        $review->update(['has_read' => YesNoEnum::YES]);
        cache()->forget('data_countExamReviewsNotRead');

        return view('backend.exams.review-detail', [
            'title' => __('Değerlendirme'),
            'review' => $review
        ]);
    }
}
