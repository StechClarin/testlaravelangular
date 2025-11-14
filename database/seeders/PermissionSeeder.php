<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'Create Tasks', 'slug' => 'tasks.create'],
            ['name' => 'Read Tasks', 'slug' => 'tasks.read'],
            ['name' => 'Update Tasks', 'slug' => 'tasks.update'],
            ['name' => 'Delete Tasks', 'slug' => 'tasks.delete'],
            ['name' => 'Manage Users', 'slug' => 'users.manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        }
    }
}
