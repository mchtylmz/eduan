<?php

use App\Helpers\TableCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('code')->unique();
            $table->string('slug', 180);
            $table->string('title', 180);
            $table->string('original_name');
            $table->string('extension', 8);
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->string('disk', 8);
            $table->string('path');
            $table->text('storage_config')->nullable();
            $table->text('public_url')->nullable();
            $table->string('status', 16)->default('uploading')->index();
            $table->unsignedBigInteger('uploaded_by')->index();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['view', 'add', 'delete'] as $operation) {
            $name = 'files:'.$operation;
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            $role = DB::table('roles')->where('name', 'Yönetici')->where('guard_name', 'web')->value('id');
            if ($role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        TableCache::invalidate('roles', 'options');
    }

    public function down(): void
    {
        // File objects and subsequent permission assignments require an explicit removal plan.
    }
};
