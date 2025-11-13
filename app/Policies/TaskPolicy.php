<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    /**
     * Méthode exécutée avant toute autre vérification.
     * Si l'utilisateur est admin, on autorise tout immédiatement.
     * C'est le "Bypass" administrateur.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Si on retourne null, la vérification continue vers les méthodes ci-dessous.
        return null;
    }

    /**
     * Determine whether the user can view any models.
     * (Pour la liste globale)
     */
    public function viewAny(User $user): bool
    {
        // Tout utilisateur authentifié peut accéder à la liste.
        // Le filtrage des données (pour qu'un user ne voie que les siennes) 
        // se fait dans le Controller via la Query, pas ici.
        return $user->hasPermissionTo('tasks.read');
    }

    /**
     * Determine whether the user can view the model.
     * (Pour le détail d'une tâche spécifique)
     */
    public function view(User $user, Task $task): bool
    {
        // Le Manager peut tout voir
        if ($user->hasRole('manager')) {
            return true;
        }

        // L'User standard ne voit la tâche que si :
        // 1. Il l'a créée
        // 2. OU elle lui est assignée
        return $user->id === $task->created_by || $user->id === $task->assigned_to;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tasks.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // Le Manager peut modifier n'importe quelle tâche
        if ($user->hasRole('manager')) {
            return true;
        }

        // L'User ne peut modifier que SES tâches
        return $user->id === $task->created_by;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        // Le Manager peut supprimer n'importe quelle tâche
        if ($user->hasRole('manager')) {
            return true;
        }

        // L'User ne peut supprimer que SES tâches
        return $user->id === $task->created_by;
    }
}