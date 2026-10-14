<?php

namespace App\Actions\Study;

use App\Actions\Users\AuthorizeStudentAccess;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeStudyAccess
{
    use AsAction;

    public function handle(User $user, bool $write = false): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('study:access'), 403);
        if ($actor->id === $user->id) {
            return;
        }
        abort_unless($actor->canany(['user-type:admin', 'user-type:teacher']), 403);
        if ($actor->can('user-type:admin')) {
            abort_unless($actor->can('dashboard:access') && $actor->can('users:view') && $actor->can($write ? 'study:update' : 'study:view'), 403);

            return;
        }
        AuthorizeStudentAccess::run($user, $write ? 'study:update' : 'study:view');
    }
}
