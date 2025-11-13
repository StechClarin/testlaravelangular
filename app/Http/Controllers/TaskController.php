<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Affiche la liste des tâches (paginée et filtrée).
     */
    public function index(Request $request)
    {
        // 1. Initialiser la query avec les relations pour éviter le N+1 problem
        $query = Task::with(['assignedTo', 'createdBy']);

        // 2. RESTRICTION DE VISIBILITÉ (Sécurité)
        // Si l'utilisateur est un simple 'user', il ne doit voir que ses tâches
        // (celles qu'il a créées OU celles qui lui sont assignées).
        // Admin et Manager voient tout.
        if ($request->user()->hasRole('user') && !$request->user()->hasRole('manager') && !$request->user()->hasRole('admin')) {
            $query->where(function($q) use ($request) {
                $q->where('created_by', $request->user()->id)
                  ->orWhere('assigned_to', $request->user()->id);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // On trie par date de création décroissante par défaut pour l'UX
        return TaskResource::collection($query->latest()->paginate(15));
    }

    /**
     * Crée une nouvelle tâche.
     */
    public function store(StoreTaskRequest $request)
    {
        // Note: La permission 'tasks.create' est gérée par le Middleware sur la route
        
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $task = Task::create($data);

        return new TaskResource($task->load(['assignedTo', 'createdBy']));
    }

    /**
     * Affiche le détail d'une tâche.
     */
    public function show(Task $task)
    {
        // Vérifie la Policy : view
        $this->authorize('view', $task);

        return new TaskResource($task->load(['assignedTo', 'createdBy']));
    }

    /**
     * Met à jour une tâche existante.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        // Vérifie la Policy : update (Manager ou Créateur uniquement)
        $this->authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task->load(['assignedTo', 'createdBy']));
    }

    /**
     * Supprime une tâche.
     */
    public function destroy(Task $task)
    {
        // Vérifie la Policy : delete (Manager ou Créateur uniquement)
        $this->authorize('delete', $task);
        
        $task->delete();

        return response()->noContent(); // 204 No Content
    }
}