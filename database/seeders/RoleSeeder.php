<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $manager = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $user = Role::create(['name' => 'User', 'slug' => 'user']);

        $permissions = Permission::all();

        $admin->permissions()->attach($permissions);

        $managerPermissions = $permissions->filter(function ($permission) {
            return in_array($permission->slug, ['tasks.create', 'tasks.read', 'tasks.update', 'tasks.delete', 'users.manage']);
        });
        $manager->permissions()->attach($managerPermissions);

        $userPermissions = $permissions->filter(function ($permission) {
            return in_array($permission->slug, ['tasks.create', 'tasks.read', 'tasks.update', 'tasks.delete']);
        });
        $user->permissions()->attach($userPermissions);
    }
}
