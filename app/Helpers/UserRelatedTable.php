<?php

namespace App\Helpers;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Actions\Users\AuthorizeTeacherAccess;
use App\Enums\RoleTypeEnum;
use App\Models\ExamResult;
use App\Models\ExamResultDetail;
use App\Models\TestsResult;
use App\Models\TestsResultDetail;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;

class UserRelatedTable
{
    public static function favorites(Request $request)
    {
        $sorts = ['language' => 'languages.name', 'code' => 'exams.code', 'name' => 'exams.name',
            'visibility' => 'exams.visibility', 'status' => 'exams.status'];
        $parameters = self::parameters($request, $sorts, 'code');
        $user = User::findOrFail($parameters['user_id']);
        AuthorizeStudentAccess::run($user);
        $query = $user->favoriteExams()->getQuery()->select('exams.*')->with('language')
            ->leftJoin('languages', 'languages.code', '=', 'exams.locale');
        self::filter($query, $parameters, 'exams', ['code', 'name', 'status', 'visibility']);
        $data = ServerTable::page('user-favorites', $query, $parameters, $sorts, fn ($exam) => [
            'id' => $exam->id, 'language' => $exam->language?->name ?? $exam->locale,
            'code' => $exam->code, 'name' => $exam->name, 'visibility' => $exam->visibility->name(),
            'status' => ['label' => $exam->status->name(), 'class' => $exam->status->class()],
        ], [$user->id]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public static function assignments(Request $request, bool $students)
    {
        $sorts = ['username' => 'users.username', 'name' => 'users.name', 'surname' => 'users.surname', 'status' => 'users.status'];
        $parameters = self::parameters($request, $sorts, 'username');
        $user = User::findOrFail($parameters['user_id']);
        if ($students) {
            AuthorizeTeacherAccess::run($user);
        } else {
            AuthorizeStudentAccess::run($user);
        }
        $query = ($students ? $user->students() : $user->teachers())->getQuery()->select('users.*');
        self::filter($query, $parameters, 'users', ['username', 'name', 'surname', 'status']);
        $permission = $students ? 'users:student-delete' : 'users:teacher-delete';
        $actor = $request->user();
        $data = ServerTable::page($students ? 'user-students' : 'user-teachers', $query, $parameters, $sorts, fn ($related) => [
            'id' => $related->id, 'username' => $related->username, 'name' => $related->name, 'surname' => $related->surname,
            'status' => ['label' => $related->status->name(), 'class' => $related->status->class()],
            'hide_detail' => true,
            'can_delete' => $actor->can($permission) && ($students || ! $actor->can(RoleTypeEnum::TEACHER->value) || $actor->id === $related->id),
        ], [$user->id]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public static function unsolvedTopics(Request $request)
    {
        $sorts = ['code' => 'topics.code', 'title' => 'topics.title->'.app()->getLocale(), 'status' => 'topics.status'];
        $parameters = self::parameters($request, $sorts, 'code');
        $user = User::findOrFail($parameters['user_id']);
        AuthorizeStudentAccess::run($user);
        // Keep the existing rule: any saved answer, including an unfinished attempt, counts as solved.
        $examTopics = ExamResultDetail::select('topic_id')->whereNotNull('topic_id')
            ->whereIn('exam_result_id', ExamResult::select('id')->where('user_id', $user->id));
        $testTopics = TestsResultDetail::select('topic_id')->whereNotNull('topic_id')
            ->whereIn('tests_result_id', TestsResult::select('id')->where('user_id', $user->id));
        $query = Topic::query()->whereNotIn('topics.id', $examTopics)->whereNotIn('topics.id', $testTopics);
        self::filter($query, $parameters, 'topics', ['code', 'title', 'status']);
        $data = ServerTable::page('user-unsolved-topics', $query, $parameters, $sorts, fn ($topic) => [
            'id' => $topic->id, 'code' => $topic->code, 'title' => $topic->title,
            'status' => ['label' => $topic->status->name(), 'class' => $topic->status->class()],
        ], [$user->id]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    private static function parameters(Request $request, array $sorts, string $default): array
    {
        abort_unless($request->user()?->can('dashboard:access') && $request->user()->can('users:view'), 403);

        return ServerTable::parameters($request, $sorts, $default, ['user_id' => ['required', 'integer', 'min:1']]);
    }

    private static function filter($query, array $parameters, string $table, array $columns): void
    {
        if (! empty($parameters['status'])) {
            $query->where($table.'.status', $parameters['status']);
        }
        if ($parameters['search'] !== '') {
            $query->where(function ($query) use ($parameters, $table, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($table.'.'.$column, 'like', '%'.$parameters['search'].'%');
                }
            });
        }
    }
}
