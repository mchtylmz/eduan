<?php

namespace App\Helpers;

use App\Enums\RoleTypeEnum;
use App\Models\ExamResult;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\TestsResult;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;

class LearningTable
{
    public static function access(string $resource): void
    {
        abort_unless(auth()->user()?->can('dashboard:access') && auth()->user()->can($resource.':view'), 403);
    }

    public static function options(): array
    {
        return [
            'languages' => TableCache::options('languages', fn () => Language::orderBy('id')->pluck('name', 'code')->all()),
            'lessons' => TableCache::options('lessons', fn () => Lesson::active()->orderBy('sort')->get()->pluck('name', 'id')->all()),
            'topics' => TableCache::options('topics', fn () => Topic::active()->orderBy('sort')->get()->pluck('title', 'id')->all()),
        ];
    }

    public static function filters(): array
    {
        return [
            'locale' => ['nullable', 'string', 'max:10', 'exists:languages,code'],
            'lessons' => ['sometimes', 'array', 'max:100'], 'lessons.*' => ['integer', 'distinct', 'exists:lessons,id'],
            'topics' => ['sometimes', 'array', 'max:100'], 'topics.*' => ['integer', 'distinct', 'exists:topics,id'],
        ];
    }

    public static function filter(Builder $query, array $parameters): void
    {
        foreach (['locale', 'status'] as $field) {
            if (($parameters[$field] ?? '') !== '') {
                $query->where($query->getModel()->qualifyColumn($field), $parameters[$field]);
            }
        }
        foreach (['lessons' => 'lesson_id', 'topics' => 'topic_id'] as $filter => $column) {
            if (! empty($parameters[$filter])) {
                $query->whereIn($query->getModel()->qualifyColumn($column), $parameters[$filter]);
            }
        }
    }

    public static function questionSorts(): array
    {
        return [
            'id' => 'questions.id', 'language' => Language::select('name')->whereColumn('languages.code', 'questions.locale'),
            'lesson_name' => Lesson::select('name->'.app()->getLocale())->whereColumn('lessons.id', 'questions.lesson_id'),
            'topic_title' => Topic::select('title->'.app()->getLocale())->whereColumn('topics.id', 'questions.topic_id'),
            'code' => 'questions.code', 'attachment' => 'questions.attachment', 'sort' => 'questions.sort', 'time' => 'questions.time', 'status' => 'questions.status',
        ];
    }

    public static function searchQuestions(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }
        $value = '%'.$search.'%';
        $query->where(fn ($query) => $query->where('questions.code', 'like', $value)->orWhere('questions.status', 'like', $value)
            ->orWhereHas('lesson', fn ($lesson) => $lesson->where('name', 'like', $value))
            ->orWhereHas('topic', fn ($topic) => $topic->where('title', 'like', $value)));
    }

    public static function questionRow(Question $question): array
    {
        return [
            'id' => $question->id, 'language' => $question->language?->name ?? $question->locale,
            'lesson_name' => $question->lesson?->name ?? '-', 'topic_title' => $question->topic?->title ?? '-',
            'code' => $question->code, 'attachment' => $question->attachment && $question->attachment_url !== '' ? asset($question->attachment_url) : null,
            'sort' => $question->sort, 'time' => $question->time,
            'status' => ['label' => $question->status->name(), 'class' => $question->status->class()],
        ];
    }

    public static function studentIds(): ?array
    {
        return auth()->user()->can(RoleTypeEnum::TEACHER->value) ? auth()->user()->students()->reorder()->orderBy('users.id')->pluck('users.id')->all() : null;
    }

    public static function timedResultsQuery(?array $students): Builder
    {
        return TestsResult::query()->when($students !== null, fn ($query) => $query->whereIn('user_id', $students));
    }

    public static function resultsQuery(?array $students): Builder
    {
        return ExamResult::query()->when($students !== null, fn ($query) => $query->whereIn('user_id', $students));
    }
}
