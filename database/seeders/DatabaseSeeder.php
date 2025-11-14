<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role; // <-- 1. Importer le modèle Role
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crée les Permissions et les Rôles
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class, // Ce seeder doit créer les rôles 'admin', 'manager', 'user'
        ]);

        // 2. Récupérer les rôles
        $adminRole = Role::where('slug', 'admin')->first();
        $userRole = Role::where('slug', 'user')->first();

        // 3. Créer 10 utilisateurs de test AVEC le rôle 'user'
        if ($userRole) {
            User::factory(10)->create()->each(function ($user) use ($userRole) {
                $user->roles()->attach($userRole);
            });
        } else {
            // Fallback si le rôle user n'est pas trouvé
            User::factory(10)->create();
        }


        // 4. Créer l'utilisateur admin SPÉCIFIQUE
        $adminUser = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
        ]);

        // 5. Attacher le rôle 'admin' à cet utilisateur
        if ($adminRole) {
            $adminUser->roles()->attach($adminRole);
        }
    }
}