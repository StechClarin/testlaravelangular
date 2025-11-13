<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
protected $policies = [
    \App\Models\Task::class => \App\Policies\TaskPolicy::class,
];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('view-tasks', function (User $user) {
            return $user->hasPermissionTo('tasks.read');
        });

        Gate::define('create-tasks', function (User $user) {
            return $user->hasPermissionTo('tasks.create');
        });

        Gate::define('update-tasks', function (User $user, Task $task) {
            if ($user->hasPermissionTo('tasks.update')) {
                return $user->id === $task->created_by || $user->id === $task->assigned_to;
            }
            return false;
        });

        Gate::define('delete-tasks', function (User $user, Task $task) {
            if ($user->hasPermissionTo('tasks.delete')) {
                return $user->id === $task->created_by;
            }
            return false;
        });

        Gate::define('manage-users', function (User $user) {
            return $user->hasPermissionTo('users.manage');
        });
    }
}
