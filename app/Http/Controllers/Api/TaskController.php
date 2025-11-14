<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 

class TaskController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste des tâches (paginée et filtrée).
     */
    public function index(Request $request)
    {
        $query = Task::with(['assignedTo', 'createdBy']);

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

        return TaskResource::collection($query->latest()->paginate(15));
    }

    /**
     * Crée une nouvelle tâche.
     */
    public function store(StoreTaskRequest $request)
    {
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
        $this->authorize('view', $task);
        return new TaskResource($task->load(['assignedTo', 'createdBy']));
    }

    /**
     * Met à jour une tâche existante.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);
        $task->update($request->validated());
        return new TaskResource($task->load(['assignedTo', 'createdBy']));
    }

    /**
     * Supprime une tâche.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->noContent();
    }
}