<?php

namespace App\Helpers;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Enums\RoleTypeEnum;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class StatisticsTable
{
    public static function authorize(?User $user = null): void
    {
        if ($user?->exists) {
            AuthorizeStudentAccess::run($user);
        } else {
            LearningTable::access('stats');
        }
    }

    public static function results(string $kind, int $userId, int $topicId, int $percent): Builder
    {
        return match ($kind) {
            'topics' => self::topics($userId, $topicId, $percent),'exams' => self::exams($userId, $topicId, $percent),'tests' => self::tests($userId, $topicId, $percent)
        };
    }

    private static function topics(int $userId, int $topicId, int $percent): Builder
    {
        $examQuery = DB::table('exam_result_details as erd')
            ->join('exam_results as er', 'erd.exam_result_id', '=', 'er.id')
            ->join('users', 'er.user_id', '=', 'users.id')
            ->join('topics', 'erd.topic_id', '=', 'topics.id')
            ->select(
                'users.id as user_id',
                'users.email',
                'users.name',
                'users.surname',
                'topics.id as topic_id',
                'topics.title as topic_title',
                DB::raw('COUNT(erd.id) as total_questions'),
                DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect')
            )
            ->groupBy('users.id', 'topics.id');

        $testQuery = DB::table('tests_result_details as erd')
            ->join('tests_results as er', 'erd.tests_result_id', '=', 'er.id')
            ->join('users', 'er.user_id', '=', 'users.id')
            ->join('topics', 'erd.topic_id', '=', 'topics.id')
            ->select(
                'users.id as user_id',
                'users.email',
                'users.name',
                'users.surname',
                'topics.id as topic_id',
                'topics.title as topic_title',
                DB::raw('COUNT(erd.id) as total_questions'),
                DB::raw('SUM(CASE WHEN erd.correct = 1 THEN 1 ELSE 0 END) as count_correct'),
                DB::raw('SUM(CASE WHEN erd.correct = 0 THEN 1 ELSE 0 END) as count_incorrect')
            )
            ->groupBy('users.id', 'topics.id');

        $unionQuery = $examQuery->unionAll($testQuery);

        $query = DB::table(DB::raw("({$unionQuery->toSql()}) as sub"))
            ->mergeBindings($unionQuery) // union'daki parametreleri aktar
            ->select(
                'user_id',
                'email',
                'name',
                'surname',
                'topic_id',
                'topic_title',
                DB::raw('SUM(total_questions) as total_questions'),
                DB::raw('SUM(count_correct) as count_correct'),
                DB::raw('SUM(count_incorrect) as count_incorrect'),
                DB::raw('ROUND(SUM(count_correct) * 100.0 / SUM(total_questions), 2) as success_rate')
            )
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(auth()->user()->can('user-type:teacher'), fn ($q) => $q->whereIn('user_id', auth()->user()->students()->select('users.id')))
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId))
            ->groupBy('user_id', 'email', 'name', 'surname', 'topic_id', 'topic_title')
            ->having('success_rate', '<=', $percent)
            ->orderBy('name')
            ->orderBy('topic_title')
            ->orderBy('success_rate', 'DESC');

        return $query;
    }

    private static function exams(int $userId, int $topicId, int $percent): Builder
    {
        $query = DB::table('exam_results as result')
            ->join('users', 'result.user_id', '=', 'users.id')
            ->join('exams', 'result.exam_id', '=', 'exams.id')
            ->select(
                'users.id as user_id',
                'exams.id as exam_id',
                'users.name as name',
                'users.surname as surname',
                'users.email as email',
                'exams.name as exam_name',
                DB::raw('SUM(result.question_count) as total_questions'),
                DB::raw('SUM(result.correct_count) as count_correct'),
                DB::raw('SUM(result.incorrect_count) as count_incorrect'),
                DB::raw('ROUND(SUM(result.correct_count) * 100.0 / NULLIF(SUM(result.question_count), 0), 2) as success_rate')
            )
            ->when($userId, function ($query) use ($userId) {
                return $query->where('users.id', $userId); // 👤 kullanıcı filtresi
            })
            ->when(auth()->user()->can('user-type:teacher'), fn ($q) => $q->whereIn('users.id', auth()->user()->students()->select('users.id')))
            ->groupBy('users.id', 'exams.id')
            ->having('success_rate', '<=', $percent) // 🎯 başarı yüzdesi filtresi
            ->orderBy('users.name')
            ->orderBy('exams.name')
            ->orderBy('success_rate', 'DESC');

        return $query;
    }

    private static function tests(int $userId, int $topicId, int $percent): Builder
    {
        $query = DB::table('tests_results as result')
            ->join('users', 'result.user_id', '=', 'users.id')
            ->join('tests', 'result.test_id', '=', 'tests.id')
            ->select(
                'users.id as user_id',
                'tests.id as test_id',
                'users.name as name',
                'users.surname as surname',
                'users.email as email',
                'tests.name as test_name',
                DB::raw('SUM(result.question_count) as total_questions'),
                DB::raw('SUM(result.correct_count) as count_correct'),
                DB::raw('SUM(result.incorrect_count) as count_incorrect'),
                DB::raw('SUM(result.point) as point'),
                DB::raw('ROUND(SUM(result.correct_count) * 100.0 / NULLIF(SUM(result.question_count), 0), 2) as success_rate')
            )
            ->when($userId, function ($query) use ($userId) {
                return $query->where('users.id', $userId); // 👤 kullanıcı filtresi
            })
            ->when(auth()->user()->can('user-type:teacher'), fn ($q) => $q->whereIn('users.id', auth()->user()->students()->select('users.id')))
            ->groupBy('users.id', 'tests.id')
            ->having('success_rate', '<=', $percent) // 🎯 başarı yüzdesi filtresi
            ->orderBy('users.name')
            ->orderBy('tests.name')
            ->orderBy('success_rate', 'DESC');

        return $query;
    }

    public static function questions(string $started, string $ended, string $order): Builder
    {
        $examResults = DB::table('exam_results')
            ->whereBetween('updated_at', [$started, $ended])
            ->select(
                'user_id',
                'question_count',
                'correct_count',
                'incorrect_count',
                'updated_at'
            );

        $testResults = DB::table('tests_results')
            ->whereBetween('updated_at', [$started, $ended])
            ->select(
                'user_id',
                'question_count',
                'correct_count',
                'incorrect_count',
                'updated_at'
            );

        $results = DB::query()
            ->fromSub(
                $examResults->unionAll($testResults),
                'results'
            )
            ->leftJoin('users', 'users.id', '=', 'results.user_id')
            ->select(
                'results.user_id',
                'users.username',
                'users.name',
                'users.surname',
                DB::raw('SUM(question_count) as question_count'),
                DB::raw('SUM(correct_count) as correct_count'),
                DB::raw('SUM(incorrect_count) as incorrect_count'),
                DB::raw('ROUND(SUM(correct_count) * 100.0 / SUM(question_count), 2) as success_rate'),
                DB::raw('MAX(results.updated_at) as updated_at')
            )
            ->when(
                auth()->user()->can(RoleTypeEnum::TEACHER),
                fn ($query) => $query->whereIn('user_id', auth()->user()->students()->select('id'))
            )
            ->groupBy(
                'results.user_id'
            )
            ->orderByRaw($order);

        return $results;
    }
}
