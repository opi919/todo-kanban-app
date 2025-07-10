<?php

namespace App\Models;

use App\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Task extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $fillable = [
        'title',
        'description',
        'status',
        'sort_order',
        'priority',
        'due_date',
        'created_by',
        'organization_id',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'due_date' => 'date',
    ];

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    public function buildSortQuery()
    {
        return static::query()->where('status', $this->status);
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    public function assignedUser(): BelongsToMany
    {
        return $this->assignedUsers();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForUser($query, $user)
    {
        if ($user->isSuperAdmin()) {
            return $query; // Super admin can see all tasks
        }

        if ($user->isOrganizationAdmin()) {
            // Organization admin can see all tasks in their organization
            return $query->where('organization_id', $user->organization_id);
        }

        // Regular users can only see tasks assigned to them
        return $query->whereHas('assignedUsers', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }

    public function scopeInOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    // Helper methods
    public function isAssignedTo($user): bool
    {
        if ($user instanceof User) {
            return $this->assignedUsers()->where('users.id', $user->id)->exists();
        }

        return $this->assignedUsers()->where('users.id', $user)->exists();
    }

    public function getAssignedUsersNamesAttribute(): string
    {
        return $this->assignedUsers->pluck('name')->join(', ');
    }

    public function getAssignedUserIdsAttribute(): array
    {
        return $this->assignedUsers->pluck('id')->toArray();
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isOrganizationAdmin()) {
            return $this->organization_id === $user->organization_id;
        }

        return $this->isAssignedTo($user);
    }
}
