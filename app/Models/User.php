<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        // Pas de mot de passe requis pour le Magic Link
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        // On cache les pivots JSON si besoin
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Pas de casting password ou verified_at requis par le doc
        ];
    }

    // Relation Users <-> Roles
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // Relation Users -> Tasks (Créées par l'user)
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    // Helper pour vérifier les rôles de l'utilisateur
    public function hasRole($role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    // Helper pour vérifier les permissions via les rôles
    public function hasPermissionTo($permission): bool
    {
        // L'admin a tous les droits [cite: 61]
        if ($this->roles->contains('slug', 'admin')) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('slug', $permission);
        })->exists();
    }
}