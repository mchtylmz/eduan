<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AuthorizeUserMail
{
    use AsAction;

    public function handle(?User $user = null, array $studentIds = []): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can('users:send-email')
            && $actor->canany(['users:view', 'stats:view']), 403);

        if ($user) {
            AuthorizeStudentAccess::run($user, 'users:send-email');
        } elseif ($studentIds && $actor->can('user-type:teacher')) {
            $ids = array_unique(array_map('intval', $studentIds));
            $count = $actor->students()->whereIn('users.id', $ids)->distinct()->count('users.id');
            abort_unless($count === count($ids), 403);
        }
    }
}
