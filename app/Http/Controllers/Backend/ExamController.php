<?php

namespace App\Http\Controllers\Backend;

use App\Enums\YesNoEnum;
use App\Helpers\LearningTable;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\ExamReview;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function getReviewsData(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('exams-reviews:view'), 403);
        $sorts = ['id' => 'exam_reviews.id', 'exam' => Exam::select('name')->whereColumn('exams.id', 'exam_reviews.exam_id'),
            'name' => User::select('name')->whereColumn('users.id', 'exam_reviews.user_id'),
            'surname' => User::select('surname')->whereColumn('users.id', 'exam_reviews.user_id'),
            'comment' => 'exam_reviews.comment', 'visibility' => 'exam_reviews.visibility', 'has_read' => 'exam_reviews.has_read', 'created_at' => 'exam_reviews.created_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'created_at', [
            'users' => ['sometimes', 'array', 'max:100'], 'users.*' => ['integer', 'distinct', 'exists:users,id'],
            'exams' => ['sometimes', 'array', 'max:100'], 'exams.*' => ['integer', 'distinct', 'exists:exams,id'],
            'has_read' => ['sometimes', 'array', 'max:2'], 'has_read.*' => ['integer', 'in:0,1', 'distinct'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = ExamReview::query()->with(['user', 'exam']);
        foreach (['users' => 'user_id', 'exams' => 'exam_id', 'has_read' => 'has_read'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereIn($column, $parameters[$filter]);
            }
        }
        foreach (['exam_id', 'user_id'] as $column) {
            if (! empty($parameters[$column])) {
                $query->where($column, $parameters[$column]);
            }
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('comment', 'like', $search)->orWhere('visibility', 'like', $search)
                ->orWhere('has_read', 'like', $search)
                ->orWhereHas('exam', fn ($exam) => $exam->where('name', 'like', $search))
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search)->orWhere('surname', 'like', $search)));
        }
        $data = ServerTable::page('reviews', $query, $parameters, $sorts, fn (ExamReview $record) => [
            'id' => $record->id, 'exam' => $record->exam?->name ?? '-', 'name' => $record->user?->name ?? '-', 'surname' => $record->user?->surname ?? '-',
            'comment' => str($record->comment)->limit(54)->toString(),
            'visibility' => ['label' => $record->visibility->name(), 'class' => 'secondary'],
            'has_read' => ['label' => YesNoEnum::YES->is($record->has_read) ? __('Okundu') : __('Okunmamış'), 'class' => YesNoEnum::YES->is($record->has_read) ? 'success' : 'warning'],
            'created_at' => dateFormat($record->created_at, 'd/m/Y, H:i'), 'view_url' => route('admin.exams.reviewDetail', $record),
            'edit_url' => null, 'can_delete' => $actor->can('exams-reviews:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function getData(Request $request)
    {
        LearningTable::access('exams');
        $sorts = ['id' => 'exams.id', 'language' => Language::select('name')->whereColumn('languages.code', 'exams.locale'),
            'code' => 'exams.code', 'name' => 'exams.name', 'questions_count' => 'questions_count', 'visibility' => 'exams.visibility', 'hits' => 'exams.hits', 'status' => 'exams.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', [...LearningTable::filters(), 'visibility' => ['nullable', 'integer', 'in:0,1']]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = Exam::select(['id', 'locale', 'code', 'name', 'visibility', 'hits', 'status'])->with('language')->withCount('questions');
        foreach (['locale', 'status', 'visibility'] as $field) {
            if (($parameters[$field] ?? '') !== '') {
                $query->where('exams.'.$field, $parameters[$field]);
            }
        }
        // Filter through the selected questions; Exam's lesson/topic relations use the question pivot.
        foreach (['lessons' => 'lesson_id', 'topics' => 'topic_id'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereHas('questions', fn ($query) => $query->whereIn('questions.'.$column, $parameters[$filter]));
            }
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('code', 'like', $value)->orWhere('name', 'like', $value)->orWhere('visibility', 'like', $value)->orWhere('status', 'like', $value));
        }
        $data = ServerTable::page('exams', $query, $parameters, $sorts, fn (Exam $exam) => [
            'id' => $exam->id, 'language' => $exam->language?->name ?? $exam->locale, 'code' => $exam->code, 'name' => $exam->name,
            'questions_count' => $exam->questions_count, 'visibility' => ['label' => $exam->visibility->name(), 'class' => 'secondary'], 'hits' => $exam->hits,
            'status' => ['label' => $exam->status->name(), 'class' => $exam->status->class()],
            'results_url' => route('admin.exams.result', $exam),
            'edit_url' => $request->user()->can('exams:update') ? route('admin.exams.edit', $exam) : null, 'can_delete' => $request->user()->can('exams:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function getResultsData(Request $request)
    {
        LearningTable::access('exams');
        $sorts = ['id' => 'exam_results.id', 'exam' => Exam::select('name')->whereColumn('exams.id', 'exam_results.exam_id'),
            'name' => User::select('name')->whereColumn('users.id', 'exam_results.user_id'), 'surname' => User::select('surname')->whereColumn('users.id', 'exam_results.user_id'),
            'email' => User::select('email')->whereColumn('users.id', 'exam_results.user_id'),
            'question_count' => 'exam_results.question_count', 'correct_count' => 'exam_results.correct_count', 'incorrect_count' => 'exam_results.incorrect_count',
            'time' => 'exam_results.time', 'completed' => 'exam_results.completed', 'updated_at' => 'exam_results.updated_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'updated_at', [
            'users' => ['sometimes', 'array', 'max:100'], 'users.*' => ['integer', 'distinct', 'exists:users,id'],
            'exams' => ['sometimes', 'array', 'max:100'], 'exams.*' => ['integer', 'distinct', 'exists:exams,id'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'], 'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'season_id' => ['nullable', 'integer', 'min:0'], 'completed' => ['nullable', 'integer', 'in:0,1'], 'incorrect_count' => ['sometimes', 'boolean'],
        ]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $students = LearningTable::studentIds();
        $query = LearningTable::resultsQuery($students)->with(['exam:id,name', 'user:id,name,surname,email']);
        foreach (['exam_id', 'user_id', 'season_id', 'completed'] as $field) {
            if (($parameters[$field] ?? '') !== '') {
                $query->where($field, $parameters[$field]);
            }
        }
        foreach (['users' => 'user_id', 'exams' => 'exam_id'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereIn($column, $parameters[$filter]);
            }
        }
        if (! empty($parameters['incorrect_count'])) {
            $query->whereColumn('question_count', 'correct_count');
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->whereHas('exam', fn ($exam) => $exam->where('name', 'like', $value))
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $value)->orWhere('surname', 'like', $value)->orWhere('email', 'like', $value))
                ->orWhere('completed', 'like', $value));
        }
        $data = ServerTable::page('exam-results', $query, $parameters, $sorts, fn (ExamResult $result) => [
            'id' => $result->id, 'exam' => $result->exam?->name ?? '-', 'name' => $result->user?->name ?? '-', 'surname' => $result->user?->surname ?? '-', 'email' => $result->user?->email ?? '-',
            'question_count' => $result->question_count, 'correct_count' => $result->correct_count, 'incorrect_count' => $result->incorrect_count,
            'time' => sprintf('∼%d %s', intval($result->time / 60), __('dakika')),
            'completed' => ['label' => YesNoEnum::YES->is($result->completed) ? __('Tamamlandı') : __('Tamamlanmadı'), 'class' => YesNoEnum::YES->is($result->completed) ? 'success' : 'warning'],
            'updated_at' => dateFormat($result->updated_at, 'd/m/Y, H:i'), 'edit_url' => null, 'can_delete' => false,
        ], [$students]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function getQuestionsData(Request $request)
    {
        LearningTable::access('exams');
        $request->validate(['exam_id' => ['required', 'integer', 'exists:exams,id']]);
        $sorts = LearningTable::questionSorts();
        $parameters = ServerTable::parameters($request, $sorts, 'status', ['exam_id' => ['required', 'integer', 'exists:exams,id']]);
        $query = Exam::findOrFail($parameters['exam_id'])->questions()->getQuery()->select('questions.*')->with(['language', 'lesson', 'topic']);
        LearningTable::filter($query, $parameters);
        LearningTable::searchQuestions($query, $parameters['search']);

        return response()->json(ServerTable::page('exam-questions', $query, $parameters, $sorts, fn (Question $question) => LearningTable::questionRow($question)))
            ->header('Cache-Control', 'private, no-store');
    }

    public function getFavoritesData(Request $request)
    {
        LearningTable::access('exams');
        $sorts = ['id' => 'users.id', 'username' => 'users.username', 'name' => 'users.name', 'surname' => 'users.surname', 'last_login_at' => 'users.last_login_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'username', ['exam_id' => ['required', 'integer', 'exists:exams,id']]);
        $query = Exam::findOrFail($parameters['exam_id'])->favorites()->getQuery()->select('users.*');
        $students = LearningTable::studentIds();
        if ($students !== null) {
            $query->whereIn('users.id', $students);
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('username', 'like', $value)->orWhere('name', 'like', $value)->orWhere('surname', 'like', $value));
        }

        return response()->json(ServerTable::page('exam-favorites', $query, $parameters, $sorts, fn (User $user) => [
            'id' => $user->id, 'username' => $user->username, 'name' => $user->name, 'surname' => $user->surname,
            'last_login_at' => $user->last_login_at ? dateFormat($user->last_login_at) : '-',
        ], [$students]))->header('Cache-Control', 'private, no-store');
    }

    public function getResultDetailsData(Request $request)
    {
        LearningTable::access('exams');
        $sorts = ['id' => 'exam_result_details.id', 'attachment' => Question::select('attachment')->whereColumn('questions.id', 'exam_result_details.question_id'),
            'answer' => Answer::select('title')->whereColumn('answers.id', 'exam_result_details.answer_id'),
            'correct' => 'exam_result_details.correct', 'time' => 'exam_result_details.time', 'updated_at' => 'exam_result_details.updated_at',
            'lesson_name' => Lesson::select('name->'.app()->getLocale())->whereColumn('lessons.id', 'exam_result_details.lesson_id'),
            'topic_title' => Topic::select('title->'.app()->getLocale())->whereColumn('topics.id', 'exam_result_details.topic_id')];
        $parameters = ServerTable::parameters($request, $sorts, 'id', [...LearningTable::filters(), 'exam_result_id' => ['required', 'integer', 'exists:exam_results,id']]);
        $students = LearningTable::studentIds();
        LearningTable::resultsQuery($students)->findOrFail($parameters['exam_result_id']);
        $query = ExamResultDetail::with(['question', 'answer', 'lesson', 'topic'])->where('exam_result_id', $parameters['exam_result_id']);
        foreach (['lessons' => 'lesson_id', 'topics' => 'topic_id'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereIn($column, $parameters[$filter]);
            }
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('correct', 'like', $value)->orWhereHas('question', fn ($question) => $question->where('attachment', 'like', $value)));
        }

        return response()->json(ServerTable::page('exam-result-details', $query, $parameters, $sorts, fn (ExamResultDetail $detail) => [
            'id' => $detail->id, 'attachment' => $detail->question?->attachment && $detail->question->attachment_url !== '' ? asset($detail->question->attachment_url) : null,
            'answer' => $detail->answer?->title ?? '-', 'correct' => ['label' => YesNoEnum::YES->is($detail->correct) ? __('Doğru') : __('Yanlış'), 'class' => YesNoEnum::YES->is($detail->correct) ? 'success' : 'danger'],
            'lesson_name' => $detail->lesson?->name ?? '-', 'topic_title' => $detail->topic?->title ?? '-', 'time' => $detail->time,
            'updated_at' => dateFormat($detail->updated_at, 'd/m/Y, H:i'),
        ], [$students]))->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.exams.index', [
            'title' => __('Testler'),
        ]);
    }

    public function create()
    {
        return view('backend.exams.create', [
            'title' => __('Test Oluştur'),
        ]);
    }

    public function edit(Exam $exam)
    {
        return view('backend.exams.edit', [
            'title' => __('Test Düzenle'),
            'exam' => $exam,
        ]);
    }

    public function results()
    {
        return view('backend.exams.results', [
            'title' => __('Test Sonuçları'),
        ]);
    }

    public function result(Exam $exam)
    {
        return view('backend.exams.result', [
            'title' => __('Test Sonucu'),
            'exam' => $exam,
        ]);
    }

    public function reviews()
    {
        return view('backend.exams.reviews', [
            'title' => __('Değerlendirmeler'),
        ]);
    }

    public function review(Exam $exam)
    {
        return view('backend.exams.reviews', [
            'title' => __('Değerlendirmeler'),
            'exam' => $exam,
        ]);
    }

    public function reviewDetail(ExamReview $review)
    {
        $review->update(['has_read' => YesNoEnum::YES]);
        cache()->forget('data_countExamReviewsNotRead');

        return view('backend.exams.review-detail', [
            'title' => __('Değerlendirme'),
            'review' => $review,
        ]);
    }
}
