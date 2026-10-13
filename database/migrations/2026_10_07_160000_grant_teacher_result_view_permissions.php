<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Identify teacher roles by their existing type permission, not a translated name.
            $roles = DB::table('roles')->where('roles.guard_name', 'web')->whereExists(function ($query) {
                $query->selectRaw('1')->from('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->whereColumn('role_has_permissions.role_id', 'roles.id')
                    ->where('permissions.guard_name', 'web')->where('permissions.name', 'user-type:teacher');
            })->pluck('id');
            if ($roles->isEmpty()) {
                return;
            }
            foreach (['exams:view', 'tests:view'] as $name) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
                $permission = DB::table('permissions')->where('guard_name', 'web')->where('name', $name)->value('id');
                DB::table('role_has_permissions')->insertOrIgnore($roles->map(fn ($id) => ['role_id' => $id, 'permission_id' => $permission])->all());
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve pre-existing grants and any subsequent manual permission decisions.
    }
};
