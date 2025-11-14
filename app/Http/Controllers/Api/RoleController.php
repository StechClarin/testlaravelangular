<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * GET /api/roles
     * Liste des rôles avec leurs permissions (pour l'affichage).
     */
    public function index()
    {
        $roles = Role::with('permissions')->get(); // Pas besoin de pagination pour les rôles généralement
        return response()->json($roles);
    }

    /**
     * POST /api/roles
     * Créer un nouveau rôle.
     */


    /**
     * GET /api/roles/{id}
     */
public function store(Request $request)
    {
        // 1. Valider le payload imbriqué
        $validated = $request->validate([
            'role.name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'role.slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'role.description' => ['nullable', 'string'],
            'permissionIds' => ['required', 'array'],
            'permissionIds.*' => ['integer', 'exists:permissions,id']
        ]);

        // 2. Créer le rôle (en utilisant 'role' de la requête)
        $role = Role::create($validated['role']);

        // 3. Attacher les permissions (en utilisant 'permissionIds')
        $role->permissions()->sync($validated['permissionIds']);

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * PUT /api/roles/{id}
     * Mettre à jour un rôle ET ses permissions.
     */
public function update(Request $request, Role $role)
    {
        // 1. Valider
        $validated = $request->validate([
            // LA CORRECTION : On spécifie la colonne 'name' et 'slug'
            'role.name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'role.slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($role->id)],
            'role.description' => ['nullable', 'string'],
            'permissionIds' => ['sometimes', 'required', 'array'],
            'permissionIds.*' => ['integer', 'exists:permissions,id']
        ]);

        // 2. Logique "Solide" : Utiliser une transaction
        try {
            DB::beginTransaction();

            if (isset($validated['role'])) {
                $role->update($validated['role']);
            }

            // 3. Gestion des impacts (ta question)
            // Si 'permissionIds' est envoyé, on synchronise.
            // sync() gère automatiquement les "impacts" : il supprime les 
            // permissions qui ne sont plus dans la liste. C'est exactement ce que tu veux.
            if (isset($validated['permissionIds'])) {
                $role->permissions()->sync($validated['permissionIds']);
            }

            DB::commit();

            return response()->json($role->load('permissions'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la mise à jour du rôle.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/roles/{id}
     */
    public function destroy(Role $role)
    {
        // Optionnel : Empêcher la suppression des rôles système
        if (in_array($role->slug, ['admin', 'manager', 'user'])) {
             return response()->json(['message' => 'Impossible de supprimer les rôles système.'], 403);
        }

        $role->delete();
        return response()->noContent();
    }

    /**
     * POST /api/roles/{id}/permissions
     * Assigner des permissions à un rôle.
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Sync met à jour la table pivot permission_role
        $role->permissions()->sync($request->permissions);

        return response()->json([
            'message' => 'Permissions mises à jour.',
            'role' => $role->load('permissions')
        ]);
    }
}