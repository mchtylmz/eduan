<?php

namespace App\Helpers;

use App\Enums\RoleTypeEnum;
use App\Enums\YesNoEnum;
use App\Models\Contact;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStats
{
    public static function get(): array
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access'), 403);
        $teacher = $actor->can(RoleTypeEnum::TEACHER->value);
        $canAi = $actor->can('ai:view');
        $students = $teacher ? $actor->students()->orderBy('users.id')->pluck('users.id')->all() : null;
        $permissions = $actor->getAllPermissions()->pluck('name')->sort()->values()->all();
        $context = [app()->getLocale(), $teacher ? $actor->id : 'all', $students, $permissions, $canAi];
        $key = 'dashboard:v2:'.hash('sha256', json_encode($context));

        return Cache::store('dashboard')->remember($key, 3600, function () use ($teacher, $students, $canAi) {
            $normal = $teacher ? User::active() : User::active()->user();
            $premium = User::active()->premiumUser();
            $new = User::active()->permission(RoleTypeEnum::USER->value)->where('created_at', '>=', now()->subDays(7));
            if ($teacher) {
                foreach ([$normal, $premium, $new] as $query) {
                    $query->whereIn('users.id', $students);
                }
            }
            $data = [
                'teacher' => $teacher,
                'updated_at' => now()->format('Y-m-d H:i:s'),
                'new_students_from' => now()->subDays(7)->format('Y-m-d\TH:i:s'),
                'registered' => $normal->count(),
                'premium' => $premium->count(),
                'new_students' => $new->count(),
                'messages' => $teacher ? null : Contact::where('has_read', YesNoEnum::NO)->count(),
                'hits' => Exam::active()->select('id', 'name', 'hits')->orderByDesc('hits')->limit(5)->get()->toArray(),
            ];

            $results = ExamResult::selectRaw('exam_id, SUM(question_count) AS question_count, SUM(correct_count) AS correct_count, SUM(incorrect_count) AS incorrect_count')
                ->with('exam:id,name')
                ->when($teacher, fn ($query) => $query->whereIn('user_id', $students))
                ->groupBy('exam_id')->orderByDesc('question_count')->orderBy('exam_id')->limit(5)->get();
            $data['results'] = $results->map(fn ($result) => [
                'id' => $result->exam_id,
                'name' => $result->exam?->name ?? '',
                'question_count' => (int) $result->question_count,
                'correct_count' => (int) $result->correct_count,
                'incorrect_count' => (int) $result->incorrect_count,
            ])->all();

            $data['ai'] = null;
            if ($canAi) {
                $data['ai'] = DB::table('user_ai_usages')
                    ->when($teacher, fn ($query) => $query->whereIn('user_ai_usages.user_id', $students))
                    ->join('answer_a_i_s', 'answer_a_i_s.id', '=', 'user_ai_usages.answer_ai_id')
                    ->join('topics', 'topics.id', '=', 'answer_a_i_s.topic_id')
                    ->select('topics.id', 'topics.title')->selectRaw('SUM(user_ai_usages.usage) AS hits')
                    ->groupBy('topics.id', 'topics.title')->orderByDesc('hits')->orderBy('topics.id')->limit(5)->get()
                    ->map(fn ($topic) => ['id' => $topic->id, 'name' => getJsonLocaleValue($topic->title), 'hits' => (int) $topic->hits])->all();
            }
            if (! $teacher) {
                $data['lessons'] = Lesson::select('id', 'name', 'hits')->orderByDesc('hits')->limit(5)->get()
                    ->map(fn ($lesson) => ['id' => $lesson->id, 'name' => $lesson->name, 'hits' => (int) $lesson->hits])->all();
                $fields = ['id', 'username', 'name', 'surname', 'email_verified', 'last_login_at', 'created_at'];
                $mapUser = fn ($user) => [
                    'id' => $user->id, 'username' => $user->username, 'name' => $user->name, 'surname' => $user->surname,
                    'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                    'email_verified' => $user->email_verified->value, 'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s'),
                ];
                $data['new_users'] = User::select($fields)->orderByDesc('id')->limit(6)->get()->map($mapUser)->all();
                $data['last_logins'] = User::select($fields)->whereNotNull('last_login_at')->orderByDesc('last_login_at')
                    ->orderByDesc('id')->limit(6)->get()->map($mapUser)->all();
            }

            return $data;
        });
    }
}
