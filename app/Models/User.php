<?php

namespace App\Models;

use App\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'organization_id',
        'admin_id',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'permissions' => 'array',
    ];
    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function managedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'admin_id');
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_user')->withTimestamps();
    }

    public function tasks(): BelongsToMany
    {
        return $this->assignedTasks();
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && is_null($this->admin_id) && is_null($this->organization_id);
    }

    // Helper methods
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true; // Super admin has all permissions
        }
        return isset($this->permissions[$permission]) && $this->permissions[$permission];
    }
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isUser(): bool
    {
        return $this->role === UserRole::User;
    }

    public function isOrganizationAdmin(): bool
    {
        return $this->isAdmin() && !is_null($this->organization_id);
    }

    // Scopes
    public function scopeRegularUsers($query)
    {
        return $query->where('role', UserRole::User);
    }

    public function scopeInOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeUnderAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    // Get users that current admin can manage
    public function getManageableUsersQuery()
    {
        if ($this->isSuperAdmin()) {
            return User::query(); // Super admin can manage all users
        }

        if ($this->isOrganizationAdmin()) {
            // Organization admin can manage users in their organization
            return User::where('organization_id', $this->organization_id)
                ->where('admin_id', $this->id);
        }

        return User::where('id', $this->id); // Regular users can only see themselves
    }

    // Get users that can be assigned tasks by current admin
    public function getAssignableUsersQuery()
    {
        if ($this->isSuperAdmin()) {
            return User::regularUsers(); // Super admin can assign to any regular user
        }

        if ($this->isOrganizationAdmin()) {
            // Organization admin can assign to their managed users only
            return User::regularUsers()
                ->where('organization_id', $this->organization_id)
                ->where('admin_id', $this->id);
        }

        return User::regularUsers()
            ->where('organization_id', $this->organization_id); // Regular users can assign task to myself only
    }

    public function getAssignedTasksCountAttribute(): int
    {
        return $this->assignedTasks()->count();
    }
}
