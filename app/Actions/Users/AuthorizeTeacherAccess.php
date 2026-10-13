<?php

namespace App\Actions\Users;

use App\Enums\RoleTypeEnum;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeTeacherAccess
{
    use AsAction;

    public function handle(User $teacher, string $permission = 'users:view'): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('users:view') && $actor->can($permission), 403);
        abort_unless($teacher->can(RoleTypeEnum::TEACHER->value), 422);
        if ($actor->can(RoleTypeEnum::TEACHER->value)) {
            abort_unless($actor->id === $teacher->id, 403);
        }
    }
}
