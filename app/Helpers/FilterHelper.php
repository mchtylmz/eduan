<?php

namespace App\Helpers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\TestsResult;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FilterHelper extends DataHelper
{
    protected string $cachePrefix = 'filter_';

    public function activeLessons(): array
    {
        return Cache::remember(
            $this->cacheKey('activeLessons'),
            $this->cacheTime(30),
            fn() => Lesson::active()
                ->orderBy('sort')
                ->get()
                ->keyBy('id')
                ->map(fn($item) => $item->name)
                ->toArray()
        );
    }
    public function activeLessonsInResult(int $examResultId): array
    {
        return Cache::remember(
            $this->cacheKey('activeLessonsInResult', $examResultId),
            $this->cacheTime(30),
            fn() => Lesson::active()
                ->whereIn('id', ExamResultDetail::where('exam_result_id', $examResultId)->pluck('lesson_id')->toArray())
                ->orderBy('sort')
                ->get()
                ->keyBy('id')
                ->map(fn($item) => $item->name)
                ->toArray()
        );
    }

    public function activeTopics(): array
    {
        return Cache::remember(
            $this->cacheKey('activeTopics'),
            $this->cacheTime(30),
            fn() => Topic::active()
                ->orderBy('sort')
                ->get()
                ->keyBy('id')
                ->map(fn($item) => $item->title)
                ->toArray()
        );
    }
    public function activeTopicsInResults(int $examResultId): array
    {
        return Cache::remember(
            $this->cacheKey('activeTopicsInResults', $examResultId),
            $this->cacheTime(30),
            fn() => Topic::active()
                ->whereIn('id', ExamResultDetail::where('exam_result_id', $examResultId)->pluck('topic_id')->toArray())
                ->orderBy('sort')
                ->get()
                ->keyBy('id')
                ->map(fn($item) => $item->title)
                ->toArray()
        );
    }

    public function users(): array
    {
        return Cache::remember(
            $this->cacheKey('users'),
            $this->cacheTime(30),
            fn() => User::orderByDesc('id')
                ->get()
                ->keyBy('id')
                ->map(fn($item) => sprintf('(%s) %s', $item->email, $item->display_name))
                ->toArray()
        );
    }

    public function usersInExamsResults(int $examId = 0): array
    {
        return Cache::remember(
            $this->cacheKey('usersInExamsResults', $examId),
            $this->cacheTime(30),
            fn() => User::orderByDesc('id')
                ->whereIn(
                    'id',
                    ExamResult::when((bool) $examId, fn ($query) => $query->where('exam_id', $examId))
                    ->pluck('user_id')
                    ->toArray()
                )
                ->get()
                ->keyBy('id')
                ->map(fn($item) => sprintf('(%s) %s', $item->email, $item->display_name))
                ->toArray()
        );
    }

    public function usersInTestsResults(int $examId = 0): array
    {
        return Cache::remember(
            $this->cacheKey('usersInTestsResults', $examId),
            $this->cacheTime(30),
            fn() => User::orderByDesc('id')
                ->whereIn(
                    'id',
                    TestsResult::when((bool) $examId, fn ($query) => $query->where('test_id', $examId))
                    ->pluck('user_id')
                    ->toArray()
                )
                ->get()
                ->keyBy('id')
                ->map(fn($item) => sprintf('(%s) %s', $item->email, $item->display_name))
                ->toArray()
        );
    }

    public function examsInResults(): array
    {
        return Cache::remember(
            $this->cacheKey('examsInResults'),
            $this->cacheTime(30),
            fn() => Exam::orderByDesc('id')
                ->whereIn('id', ExamResult::all()->pluck('exam_id')->toArray())
                ->get()
                ->keyBy('id')
                ->map(fn($item) => sprintf('(%s) %s', $item->code, $item->name))
                ->toArray()
        );
    }

    public function testsInResults(): array
    {
        return Cache::remember(
            $this->cacheKey('testsInResults'),
            $this->cacheTime(30),
            fn() => Test::orderByDesc('id')
                ->whereIn('id', TestsResult::all()->pluck('test_id')->toArray())
                ->get()
                ->keyBy('id')
                ->map(fn($item) => sprintf('(%s) %s', $item->code, $item->name))
                ->toArray()
        );
    }

    public function exams(): array
    {
        return Cache::remember(
            $this->cacheKey('exams'),
            $this->cacheTime(30),
            fn() => Exam::orderByDesc('id')
                ->get()
                ->keyBy('id')
                ->map(fn($item) => sprintf('(%s) %s', $item->code, $item->name))
                ->toArray()
        );
    }

    public function logTypes(string $value = null): string|array
    {
        $types = [
            '' => __('Tümü'),
            'login' => __('Giriş'),
            'logout' => __('Çıkış'),
            'create' => __('Ekle'),
            'edit' => __('Düzenle'),
            'delete' => __('Sil / Kaldır'),
        ];
        if (!is_null($value)) {
            return $types[$value] ?? $value;
        }

        return $types;
    }
}
