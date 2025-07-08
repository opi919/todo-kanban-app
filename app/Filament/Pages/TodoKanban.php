<?php

namespace App\Filament\Pages;

use App\Models\Todo;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Illuminate\Support\Collection;

class TodoKanban extends KanbanBoard
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static string $model = Todo::class;
    protected static string $recordTitleAttribute = 'title';
    protected static string $recordStatusAttribute = 'status';
    protected static string $view = 'filament-kanban::kanban-board';
    protected static string $headerView = 'filament-kanban::kanban-header';
    protected static string $recordView = 'filament-kanban::kanban-record';
    protected static string $statusView = 'filament-kanban::kanban-status';
    protected static string $scriptsView = 'filament-kanban::kanban-scripts';
    protected static ?string $title = 'Todo Kanban Board';

    public function statuses(): Collection
    {
        return collect([
            [
                'id' => 'pending',
                'title' => 'Pending Tasks',
            ],
            [
                'id' => 'in_progress', 
                'title' => 'In Progress',
            ],
            [
                'id' => 'completed',
                'title' => 'Completed',
            ],
        ]);
    }

    protected function records(): Collection
    {
        $query = Todo::query();

        // Admins see all, regular users see only their own
        if (!auth()->user()->is_admin) {
            $query->where('user_id', auth()->id());
        }

        return $query->get();
    }

    protected function getRecordTitle($record): string
    {
        return $record->title;
    }

    protected function getRecordSubtitle($record): ?string
    {
        $due = $record->due_date ? $record->due_date : 'No due date';
        $assignedTo = $record->user?->name ?? 'Unassigned';
        return "Due: {$due} | To: {$assignedTo}";
    }

    protected function getRecordDescription($record): ?string
    {
        $priority = ucfirst($record->priority ?? 'medium');
        $assignedTo = $record->user ? $record->user->name : 'Unassigned';
        return "Priority: {$priority} | Assigned to: {$assignedTo}";
    }

    protected function getRecordStatus($record): string
    {
        return $record->status;
    }

    public function canEdit($record): bool
    {
        return auth()->user()->is_admin || $record->user_id === auth()->id();
    }

    public function canDelete($record): bool
    {
        return auth()->user()->is_admin || $record->user_id === auth()->id();
    }
}