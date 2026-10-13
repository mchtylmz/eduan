<?php

namespace App\Actions\Users;

use App\Enums\RoleTypeEnum;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeStudentAccess
{
    use AsAction;

    public function handle(User $user, string $permission = 'users:view'): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('users:view') && $actor->can($permission), 403);
        if ($actor->can(RoleTypeEnum::TEACHER->value)) {
            abort_unless($actor->students()->where('users.id', $user->id)->exists(), 403);
        }
    }
}
