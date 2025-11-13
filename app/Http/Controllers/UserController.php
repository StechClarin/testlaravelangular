<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Liste paginée des utilisateurs.
     * Accessible aux Managers et Admins.
     */
    public function index()
    {
        // Pagination demandée dans le tableau 
        // On inclut les rôles pour l'affichage dans le tableau Angular
        $users = User::with('roles')->paginate(15);

        return response()->json($users);
    }

    /**
     * GET /api/users/{id}
     * Détail d'un utilisateur.
     */
    public function show(User $user)
    {
        return response()->json($user->load(['roles', 'roles.permissions']));
    }

    /**
     * PUT /api/users/{id}
     * Modifier un utilisateur (Nom, Email).
     */
    public function update(Request $request, User $user)
    {
        // Validation inline (ou via FormRequest si tu veux plus de points SOLID)
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * DELETE /api/users/{id}
     * Supprimer un utilisateur.
     * STRICTEMENT réservé aux admins[cite: 189].
     */
    public function destroy(User $user)
    {
        // La protection est déjà faite par le middleware 'permission:users.delete' dans les routes,
        // mais une double vérification ne fait jamais de mal (Defense in Depth).
        if (auth()->user()->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous supprimer vous-même.'], 400);
        }

        $user->delete();

        return response()->noContent(); // 204 No Content
    }

    /**
     * POST /api/users/{id}/roles
     * Assigner des rôles à un utilisateur.
     * Endpoint spécifique demandé.
     */
    public function assignRoles(Request $request, User $user)
    {
        // Validation : on s'attend à un tableau d'IDs de rôles
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'], // Vérifie que chaque ID de rôle existe
        ]);

        // Sync permet d'ajouter les nouveaux et supprimer ceux qui ne sont pas dans la liste
        // Utile pour la gestion par "Checkboxes" demandée côté Front 
        $user->roles()->sync($request->roles);

        return response()->json([
            'message' => 'Rôles mis à jour avec succès.',
            'user' => $user->load('roles')
        ]);
    }
}