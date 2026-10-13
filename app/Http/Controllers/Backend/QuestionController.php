<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\LearningTable;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function getData(Request $request)
    {
        LearningTable::access('questions');
        $sorts = LearningTable::questionSorts();
        $parameters = ServerTable::parameters($request, $sorts, 'sort', LearningTable::filters());
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Question::with(['language', 'lesson', 'topic']);
        LearningTable::filter($query, $parameters);
        LearningTable::searchQuestions($query, $parameters['search']);
        $data = ServerTable::page('questions', $query, $parameters, $sorts, fn (Question $question) => [
            ...LearningTable::questionRow($question),
            'edit_url' => $request->user()->can('questions:update') ? route('admin.questions.edit', $question) : null,
            'can_delete' => $request->user()->can('questions:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.questions.index', [
            'title' => __('Soru Havuzu'),
        ]);
    }

    public function create()
    {
        return view('backend.questions.create', [
            'title' => __('Soru Ekle'),
        ]);
    }

    public function edit(Question $question)
    {
        return view('backend.questions.edit', [
            'title' => __('Soru Düzenle'),
            'question' => $question,
        ]);
    }
}
