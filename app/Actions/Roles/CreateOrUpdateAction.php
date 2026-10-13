<?php

namespace App\Actions\Roles;

use App\Helpers\TableCache;
use App\Models\Role;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Permission;

class CreateOrUpdateAction
{
    use AsAction;

    public function handle(string $name, array $permissions = [], ?Role $role = null): void
    {
        if (is_null($role)) {
            $role = Role::create(['name' => $name]);
        } else {
            $role->update(['name' => $name]);
        }

        $permissions = $this->permissions($permissions);

        $role->syncPermissions($permissions);
        TableCache::invalidate('roles', 'users', 'options');
    }

    protected function permissions(array $permissions)
    {
        return Permission::whereIn('name', $permissions)->get();
    }
}
