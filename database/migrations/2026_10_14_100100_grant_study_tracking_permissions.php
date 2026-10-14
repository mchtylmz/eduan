<?php

use App\Helpers\TableCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id');
            $panelRoles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.guard_name', 'web')->whereIn('permissions.name', ['user-type:admin', 'user-type:teacher'])
                ->whereIn('role_id', $roles)->distinct()->pluck('role_id');
            foreach (['study:access' => $roles, 'study:view' => $panelRoles, 'study:update' => $panelRoles] as $name => $roleIds) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
                $id = DB::table('permissions')->where('guard_name', 'web')->where('name', $name)->value('id');
                foreach ($roleIds as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $id]);
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        TableCache::invalidate('roles', 'options');
    }

    public function down(): void
    {
        // Preserve existing and subsequently edited permission grants.
    }
};
