<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = Role::create(['name' => 'Normal Üye']);

        settings()->set([
            'defaultRole' => $user->id
        ]);
        settings()->save();

        $premium = Role::create(['name' => 'Premium Üye']);
        $admin = Role::create(['name' => 'Yönetici']);

        Permission::insert([
            ['name' => 'blogs:view'],
            ['name' => 'blogs:add'],
            ['name' => 'blogs:update'],
            ['name' => 'blogs:delete'],

            ['name' => 'lessons:view'],
            ['name' => 'lessons:add'],
            ['name' => 'lessons:update'],
            ['name' => 'lessons:delete'],

            ['name' => 'topics:view'],
            ['name' => 'topics:add'],
            ['name' => 'topics:update'],
            ['name' => 'topics:delete'],

            ['name' => 'questions:view'],
            ['name' => 'questions:add'],
            ['name' => 'questions:update'],
            ['name' => 'questions:delete'],

            ['name' => 'exams:view'],
            ['name' => 'exams:add'],
            ['name' => 'exams:update'],
            ['name' => 'exams:delete'],
            ['name' => 'exams:solve'],

            ['name' => 'exams-reviews:view'],
            ['name' => 'exams-reviews:update'],
            ['name' => 'exams-reviews:delete'],

            ['name' => 'users:view'],
            ['name' => 'users:add'],
            ['name' => 'users:update'],
            ['name' => 'users:update-password'],
            ['name' => 'users:delete'],

            ['name' => 'roles:view'],
            ['name' => 'roles:add'],
            ['name' => 'roles:update'],
            ['name' => 'roles:delete'],

            ['name' => 'pages:view'],
            ['name' => 'pages:add'],
            ['name' => 'pages:update'],
            ['name' => 'pages:delete'],

            ['name' => 'newsletter:view'],
            ['name' => 'newsletter:send'],
            ['name' => 'newsletter:delete'],

            ['name' => 'contacts:view'],

            ['name' => 'languages:view'],
            ['name' => 'languages:add'],
            ['name' => 'languages:update'],
            ['name' => 'languages:delete'],
            ['name' => 'languages:translate'],

            ['name' => 'settings:view'],
            ['name' => 'settings:update'],

            ['name' => 'user-type:admin'],
            ['name' => 'user-type:user'],

            ['name' => 'dashboard:access'],

            // 16-04-2025 updated
            ['name' => 'stats:view'],

            ['name' => 'tests:view'],
            ['name' => 'tests:add'],
            ['name' => 'tests:update'],
            ['name' => 'tests:delete'],
            ['name' => 'tests:solve'],

            // 18-08-2025 updated
            ['name' => 'ai:solution'],
            ['name' => 'ai:solution-vote'],
            ['name' => 'ai:view'],
            ['name' => 'ai:add'],
            ['name' => 'ai:update'],
            ['name' => 'ai:delete'],
        ]);

        $user->givePermissionTo(['user-type:user']);
        $premium->givePermissionTo(['user-type:user']);
        $premium->givePermissionTo(['exams:solve']);

        $admin->givePermissionTo(Permission::all());
        $admin->revokePermissionTo(['user-type:user']);
    }
}
