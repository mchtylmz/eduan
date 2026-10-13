<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function getData(Request $request)
    {
        abort_unless($request->user()?->can('dashboard:access') && $request->user()->can('lessons:view'), 403);
        $sorts = ['code' => 'lessons.code', 'name' => 'lessons.name->'.app()->getLocale(),
            'topics_count' => 'topics_count', 'questions_count' => 'questions_count',
            'sort' => 'lessons.sort', 'hits' => 'lessons.hits', 'status' => 'lessons.status'];
        $parameters = ServerTable::parameters($request, $sorts, 'sort', [
            'locale' => ['nullable', 'string', 'max:10', 'exists:languages,code'],
        ]);
        $query = Lesson::query()->withCount(['topics', 'questions']);
        if (! empty($parameters['locale'])) {
            $query->whereLocale('name', $parameters['locale']);
        }
        if (! empty($parameters['status'])) {
            $query->where('lessons.status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $search = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('lessons.code', 'like', $search)
                ->orWhere('lessons.name', 'like', $search)->orWhere('lessons.status', 'like', $search));
        }
        $data = ServerTable::page('lessons', $query, $parameters, $sorts, fn (Lesson $lesson) => [
            'id' => $lesson->id, 'code' => $lesson->code, 'name' => $lesson->name,
            'topics_count' => $lesson->topics_count, 'questions_count' => $lesson->questions_count,
            'sort' => $lesson->sort, 'hits' => $lesson->hits,
            'status' => ['label' => $lesson->status->name(), 'class' => $lesson->status->class()],
            'edit_url' => $request->user()->can('lessons:update') ? route('admin.lessons.edit', $lesson) : null,
            'can_delete' => $request->user()->can('lessons:delete'),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.lessons.index', [
            'title' => __('Dersler'),
        ]);
    }

    public function create()
    {
        return view('backend.lessons.create', [
            'title' => __('Ders Ekle'),
        ]);
    }

    public function edit(Lesson $lesson)
    {
        return view('backend.lessons.edit', [
            'title' => __('Ders Düzenle'),
            'lesson' => $lesson,
        ]);
    }
}
