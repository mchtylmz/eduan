<?php

namespace App\Actions\Users;

use App\Helpers\TableCache;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    use AsAction;

    public function handle(User $user, int $role_id): User
    {
        $user->syncRoles(Role::findById($role_id));
        TableCache::invalidate('roles', 'users', 'options');

        return $user;
    }
}
