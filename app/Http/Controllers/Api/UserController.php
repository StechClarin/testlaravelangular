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
        $users = User::with('roles')->paginate(15);

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json($user->load(['roles', 'roles.permissions']));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        if (auth()->user()->id === $user->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous supprimer vous-même.'], 400);
        }

        $user->delete();

        return response()->noContent(); 
    }

    public function assignRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'], 
        ]);

        $user->roles()->sync($request->roles);

        return response()->json([
            'message' => 'Rôles mis à jour avec succès.',
            'user' => $user->load('roles')
        ]);
    }
}