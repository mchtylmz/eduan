<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function getData(Request $request)
    {
        abort_unless($request->user()?->can('dashboard:access') && $request->user()->can('topics:view'), 403);
        $sorts = ['code' => 'topics.code', 'title' => 'topics.title->'.app()->getLocale(),
            'lesson_name' => Lesson::select('name->'.app()->getLocale())->whereColumn('lessons.id', 'topics.lesson_id'),
            'questions_count' => 'questions_count', 'sort' => 'topics.sort', 'hits' => 'topics.hits', 'status' => 'topics.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'sort', [
            'locale' => ['nullable', 'string', 'max:10', 'exists:languages,code'],
            'lessons' => ['sometimes', 'array', 'max:100'],
            'lessons.*' => ['integer', 'distinct', 'exists:lessons,id'],
        ]);
        $query = Topic::query()->with('lesson')->withCount('questions');
        if (! empty($parameters['locale'])) {
            $query->whereLocale('title', $parameters['locale']);
        }
        if (! empty($parameters['lessons'])) {
            $query->whereIn('topics.lesson_id', $parameters['lessons']);
        }
        if (! empty($parameters['status'])) {
            $query->where('topics.status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('topics.code', 'like', $search)
                ->orWhere('topics.title', 'like', $search)->orWhere('topics.status', 'like', $search)
                ->orWhereHas('lesson', fn ($lesson) => $lesson->where('name', 'like', $search)));
        }
        $data = ServerTable::page('topics', $query, $parameters, $sorts, fn (Topic $topic) => [
            'id' => $topic->id, 'code' => $topic->code, 'title' => $topic->title,
            'lesson_name' => $topic->lesson?->name ?? '-', 'questions_count' => $topic->questions_count,
            'sort' => $topic->sort, 'hits' => $topic->hits,
            'status' => ['label' => $topic->status->name(), 'class' => $topic->status->class()],
            'edit_url' => $request->user()->can('topics:update') ? route('admin.topics.edit', $topic) : null,
            'can_delete' => $request->user()->can('topics:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.topics.index', [
            'title' => __('Konular'),
        ]);
    }

    public function create()
    {
        return view('backend.topics.create', [
            'title' => __('Konu Ekle'),
        ]);
    }

    public function edit(Topic $topic)
    {
        return view('backend.topics.edit', [
            'title' => __('Konu Düzenle'),
            'topic' => $topic,
        ]);
    }
}
