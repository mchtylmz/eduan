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
            DB::table('permissions')->insertOrIgnore(['name' => 'users:impersonate', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $permission = DB::table('permissions')->where('name', 'users:impersonate')->where('guard_name', 'web')->value('id');
            $role = DB::table('roles')->where('name', 'Yönetici')->where('guard_name', 'web')->value('id');
            if ($role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        TableCache::invalidate('roles', 'options');
    }

    public function down(): void
    {
        // Later manual permission assignments must survive a rollback.
    }
};
