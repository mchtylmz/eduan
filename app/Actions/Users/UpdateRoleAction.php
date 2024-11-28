<?php

namespace App\Actions\Users;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    use AsAction;

    public function handle(User $user, int $role_id): User
    {
        return $user->syncRoles(Role::findById($role_id));
    }
}
