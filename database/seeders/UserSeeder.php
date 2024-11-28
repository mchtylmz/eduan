<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'username' => 'admin@admin.com',
            'email' => 'admin@admin.com',
        ]);
        $admin->assignRole(Role::findById(3));

        User::factory(250)->create();

        foreach (User::where('id', '>=', 2)->get() as $user) {
            $user->assignRole(Role::findById(1));
        }
    }
}
