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
            $roles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                ->where('roles.guard_name', 'web')->where('permissions.guard_name', 'web')
                ->where('permissions.name', 'user-type:admin')->distinct()->pluck('roles.id');
            DB::table('permissions')->insertOrIgnore(['name' => 'study:communicate', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('permissions')->where('name', 'study:communicate')->where('guard_name', 'web')->value('id');
            foreach ($roles as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        TableCache::invalidate('roles', 'options');
    }

    public function down(): void
    {
        // Existing grants and subsequent permission edits are preserved.
    }
};
