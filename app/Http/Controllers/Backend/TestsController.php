<?php

namespace App\Http\Controllers\Backend;

use App\Enums\YesNoEnum;
use App\Helpers\LearningTable;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\TestsResultDetail;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;

class TestsController extends Controller
{
    public function getData(Request $request)
    {
        LearningTable::access('tests');
        $sorts = ['id' => 'tests.id', 'language' => Language::select('name')->whereColumn('languages.code', 'tests.locale'),
            'code' => 'tests.code', 'name' => 'tests.name', 'duration' => 'tests.duration', 'passing_score' => 'tests.passing_score',
            'sections_count' => 'sections_count', 'questions_count' => 'questions_count', 'hits' => 'tests.hits', 'status' => 'tests.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', ['locale' => ['nullable', 'exists:languages,code']]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Test::select(['id', 'locale', 'code', 'name', 'duration', 'passing_score', 'hits', 'status'])->with('language')
            ->withCount(['sections_with_no_parent as sections_count', 'questions']);
        foreach (['locale', 'status'] as $field) {
            if (($parameters[$field] ?? '') !== '') {
                $query->where($field, $parameters[$field]);
            }
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('code', 'like', $value)->orWhere('name', 'like', $value)->orWhere('status', 'like', $value));
        }

        return response()->json(ServerTable::page('tests', $query, $parameters, $sorts, fn (Test $test) => [
            'id' => $test->id, 'language' => $test->language?->name ?? $test->locale, 'code' => $test->code, 'name' => $test->name,
            'duration' => formatSecondToTime(secondToTime($test->duration), hideSeconds: true), 'passing_score' => $test->passing_score,
            'sections_count' => $test->sections_count, 'questions_count' => $test->questions_count, 'hits' => $test->hits,
            'status' => ['label' => $test->status->name(), 'class' => $test->status->class()],
            'edit_url' => $request->user()->can('tests:update') ? route('admin.tests.edit', $test) : null,
            'sections_url' => $request->user()->can('tests:update') ? route('admin.tests.sections', $test) : null,
            'can_delete' => $request->user()->can('tests:delete'),
        ]))->header('Cache-Control', 'private, no-store');
    }

    public function getResultsData(Request $request)
    {
        LearningTable::access('tests');
        $sorts = ['id' => 'tests_results.id', 'test' => Test::select('name')->whereColumn('tests.id', 'tests_results.test_id'),
            'name' => User::select('name')->whereColumn('users.id', 'tests_results.user_id'), 'surname' => User::select('surname')->whereColumn('users.id', 'tests_results.user_id'),
            'email' => User::select('email')->whereColumn('users.id', 'tests_results.user_id'),
            'question_count' => 'tests_results.question_count', 'correct_count' => 'tests_results.correct_count', 'incorrect_count' => 'tests_results.incorrect_count',
            'duration' => 'tests_results.duration', 'point' => 'tests_results.point', 'completed' => 'tests_results.completed', 'completed_at' => 'tests_results.completed_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'completed_at', [
            'users' => ['sometimes', 'array', 'max:100'], 'users.*' => ['integer', 'distinct', 'exists:users,id'],
            'tests' => ['sometimes', 'array', 'max:100'], 'tests.*' => ['integer', 'distinct', 'exists:tests,id'],
            'test_id' => ['nullable', 'integer', 'exists:tests,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'season_id' => ['nullable', 'integer', 'min:0'], 'completed' => ['nullable', 'integer', 'in:0,1'], 'incorrect_count' => ['sometimes', 'boolean'],
        ]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $students = LearningTable::studentIds();
        $query = LearningTable::timedResultsQuery($students)->with(['test:id,name', 'user:id,name,surname,email']);
        foreach (['test_id', 'user_id', 'season_id', 'completed'] as $field) {
            if (($parameters[$field] ?? '') !== '') {
                $query->where($field, $parameters[$field]);
            }
        }
        foreach (['users' => 'user_id', 'tests' => 'test_id'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereIn($column, $parameters[$filter]);
            }
        }
        if (! empty($parameters['incorrect_count'])) {
            $query->whereColumn('question_count', 'correct_count');
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->whereHas('test', fn ($exam) => $exam->where('name', 'like', $value))
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $value)->orWhere('surname', 'like', $value)->orWhere('email', 'like', $value))
                ->orWhere('completed', 'like', $value));
        }
        $data = ServerTable::page('test-results', $query, $parameters, $sorts, fn (TestsResult $result) => [
            'id' => $result->id, 'test' => $result->test?->name ?? '-', 'name' => $result->user?->name ?? '-', 'surname' => $result->user?->surname ?? '-', 'email' => $result->user?->email ?? '-',
            'question_count' => $result->question_count, 'correct_count' => $result->correct_count, 'incorrect_count' => $result->incorrect_count,
            'duration' => formatSecondToTime(secondToTime($result->duration), hideSeconds: true),
            'point' => ['label' => sprintf('%d (%s)', $result->point, $result->point >= $result->passing_score ? __('Geçti') : __('Kaldı')), 'class' => $result->point >= $result->passing_score ? 'success' : 'warning'],
            'completed' => ['label' => YesNoEnum::YES->is($result->completed) ? __('Tamamlandı') : __('Tamamlanmadı'), 'class' => YesNoEnum::YES->is($result->completed) ? 'success' : 'warning'],
            'completed_at' => $result->completed_at ? dateFormat($result->completed_at, 'd/m/Y, H:i') : '-', 'edit_url' => null, 'can_delete' => false,
        ], [$students]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function getResultDetailsData(Request $request)
    {
        LearningTable::access('tests');
        $sorts = ['id' => 'tests_result_details.id', 'attachment' => Question::select('attachment')->whereColumn('questions.id', 'tests_result_details.question_id'),
            'answer' => Answer::select('title')->whereColumn('answers.id', 'tests_result_details.answer_id'),
            'correct' => 'tests_result_details.correct', 'point' => 'tests_result_details.point', 'updated_at' => 'tests_result_details.updated_at',
            'lesson_name' => Lesson::select('name->'.app()->getLocale())->whereColumn('lessons.id', 'tests_result_details.lesson_id'),
            'topic_title' => Topic::select('title->'.app()->getLocale())->whereColumn('topics.id', 'tests_result_details.topic_id')];
        $parameters = ServerTable::parameters($request, $sorts, 'id', [...LearningTable::filters(), 'tests_result_id' => ['required', 'integer', 'exists:tests_results,id']]);
        $students = LearningTable::studentIds();
        LearningTable::timedResultsQuery($students)->findOrFail($parameters['tests_result_id']);
        $query = TestsResultDetail::with(['question', 'answer', 'lesson', 'topic'])->where('tests_result_id', $parameters['tests_result_id']);
        foreach (['lessons' => 'lesson_id', 'topics' => 'topic_id'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereIn($column, $parameters[$filter]);
            }
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('correct', 'like', $value)->orWhereHas('question', fn ($question) => $question->where('attachment', 'like', $value)));
        }

        return response()->json(ServerTable::page('test-result-details', $query, $parameters, $sorts, fn (TestsResultDetail $detail) => [
            'id' => $detail->id, 'attachment' => $detail->question?->attachment && $detail->question->attachment_url !== '' ? asset($detail->question->attachment_url) : null,
            'answer' => $detail->answer?->title ?? '-', 'correct' => ['label' => $detail->correct === null ? __('Boş') : (YesNoEnum::YES->is($detail->correct) ? __('Doğru') : __('Yanlış')), 'class' => $detail->correct === null ? 'warning' : (YesNoEnum::YES->is($detail->correct) ? 'success' : 'danger')],
            'lesson_name' => $detail->lesson?->name ?? '-', 'topic_title' => $detail->topic?->title ?? '-', 'point' => $detail->point,
            'updated_at' => dateFormat($detail->updated_at, 'd/m/Y, H:i'),
        ], [$students]))->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.tests.index', [
            'title' => __('Sınavlar'),
        ]);
    }

    public function create()
    {
        return view('backend.tests.create', [
            'title' => __('Sınav Oluştur'),
        ]);
    }

    public function edit(Test $test)
    {
        return view('backend.tests.edit', [
            'title' => __('Sınavı Düzenle'),
            'test' => $test,
        ]);
    }

    public function sections(Test $test)
    {
        return view('backend.tests.sections', [
            'title' => __('Sınavı Bölümleri'),
            'test' => $test,
            'tinymce' => true,
        ]);
    }

    public function results()
    {
        return view('backend.tests.results', [
            'title' => __('Sınav Sonuçları'),
        ]);
    }

    public function result(Test $test)
    {
        return view('backend.tests.result', [
            'title' => __('Sınav Sonucu'),
            'test' => $test,
        ]);
    }
}
