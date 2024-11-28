<?php

namespace App\Actions\Roles;

use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class CreateOrUpdateAction
{
    use AsAction;

    public function handle(string $name, array $permissions = [], Role $role = null): void
    {
        if (is_null($role)) {
            $role = Role::create(['name' => $name]);
        } else {
            $role->update(['name' => $name]);
        }

        $permissions = $this->permissions($permissions);

        $role->syncPermissions($permissions);
    }

    protected function permissions(array $permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }
}
