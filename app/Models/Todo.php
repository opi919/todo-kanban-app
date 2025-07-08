<?php

namespace App\Models;

use App\TodoStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Todo extends Model implements Sortable
{
    use HasFactory, SortableTrait;

    protected $fillable = ['title', 'user_id', 'status', 'priority', 'due_date', 'order_column'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
