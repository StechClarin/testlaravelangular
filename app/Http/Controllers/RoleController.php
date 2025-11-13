<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'description' => ['nullable', 'string'],
        ]);

        $role = Role::create($validated);

        return response()->json($role, 201);
    }

    /**
     * GET /api/roles/{id}
     */
    public function show(Role $role)
    {
        return response()->json($role->load('permissions'));
    }

    /**
     * PUT /api/roles/{id}
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            // On ignore l'ID du rôle actuel pour la validation d'unicité
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'description' => ['nullable', 'string'],
        ]);

        $role->update($validated);

        return response()->json($role);
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