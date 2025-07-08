<?php

namespace App\Filament\Pages;

use App\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class TasksKanbanBoard extends KanbanBoard
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationGroup = 'Task Management';
    protected static ?int $navigationSort = 1;
    protected static string $model = Task::class;
    protected static string $statusEnum = TaskStatus::class;

    protected function getEloquentQuery(): Builder
    {
        return Task::query()->forUser(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $count = Task::query()->forUser($user)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    protected function getHeaderActions(): array
    {
        if (!auth()->user()->isAdmin()) {
            return [];
        }

        return [
            \Filament\Actions\Action::make('create')
                ->label('Create Task')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form($this->getCreateModalFormSchema())
                ->action(function (array $data) {
                    $data['status'] = TaskStatus::Pending->value;
                    $data['created_by'] = auth()->id();
                    Task::create($data);
                    $this->notify('success', 'Task created successfully.');
                }),
        ];
    }

    protected function getCreateActionLabel(): string
    {
        return 'Create Task';
    }

    protected function getEditModalFormSchema(string|int|null $recordId): array
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return [
                TextInput::make('title')
                    ->required(),

                Textarea::make('description')
                    ->rows(3),

                Select::make('status')
                    ->options(TaskStatus::class)
                    ->required(),

                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->required(),

                Select::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable(),

                DatePicker::make('due_date'),
            ];
        }

        // Regular users can only update status
        return [
            TextInput::make('title')
                ->disabled(),

            Textarea::make('description')
                ->disabled()
                ->rows(3),

            Select::make('status')
                ->options(TaskStatus::class)
                ->required(),

            Select::make('priority')
                ->disabled()
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ]),

            DatePicker::make('due_date')
                ->disabled(),
        ];
    }

    protected function getCreateModalFormSchema(): array
    {
        // Only admins can create tasks
        if (!auth()->user()->isAdmin()) {
            return [];
        }

        return [
            TextInput::make('title')
                ->required(),

            Textarea::make('description')
                ->rows(3),

            Select::make('priority')
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ])
                ->default('medium')
                ->required(),

            Select::make('assigned_to')
                ->label('Assigned To')
                ->options(User::all()->pluck('name', 'id'))
                ->searchable(),

            DatePicker::make('due_date'),

            Hidden::make('created_by')
                ->default(auth()->id()),
        ];
    }

    // // Missing method: Handle status changes when dragging tasks
    public function onStatusChanged(string|int $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $task = Task::find($recordId);

        $task->update(['status' => $status]);

        // Update sort order for all tasks in the destination column
        Task::setNewOrder($toOrderedIds);
    }

    // // Missing method: Handle sorting within the same column
    public function onSortChanged(string|int $recordId, string $status, array $orderedIds): void
    {
        $task = Task::find($recordId);


        Task::setNewOrder($orderedIds);
    }

    // Missing method: Handle record editing
    protected function editRecord($recordId, array $data, array $state): void
    {
        $task = Task::find($recordId);

        // Check if user can edit this task
        if (!auth()->user()->isAdmin() && $task->assigned_to !== auth()->id()) {
            $this->notify('danger', 'You can only edit tasks assigned to you.');
            return;
        }

        // For regular users, only allow status updates
        if (!auth()->user()->isAdmin()) {
            $task->update([
                'status' => $data['status'] ?? $task->status,
            ]);
        } else {
            // Admins can update all fields
            $task->update($data);
        }

        $this->notify('success', 'Task updated successfully.');
    }

    // Optional: Customize the record title shown on cards
    protected function getRecordTitle(?object $record): string
    {
        return $record->title ?? 'Untitled Task';
    }

    protected function getCardSubtitle(?object $record): string
    {
        $subtitle = [];

        if ($record->assignedUser) {
            $subtitle[] = "👤 {$record->assignedUser->name}";
        }

        if ($record->due_date) {
            $dueDate = $record->due_date->format('M j, Y');
            $isOverdue = $record->due_date->isPast() && $record->status !== TaskStatus::Completed;
            $subtitle[] = ($isOverdue ? "⚠️ " : "📅 ") . $dueDate;
        }

        if ($record->priority && $record->priority !== 'medium') {
            $priorityIcon = match ($record->priority) {
                'urgent' => '🔴',
                'high' => '🟡',
                'low' => '🟢',
                default => '',
            };
            $subtitle[] = $priorityIcon . ucfirst($record->priority);
        }

        return implode(' • ', $subtitle);
    }

    protected function getCardColor(?object $record): string
    {
        // Check if task is overdue
        if ($record->due_date && $record->due_date->isPast() && $record->status !== TaskStatus::Completed) {
            return 'danger';
        }

        return match ($record->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'gray',
            default => 'gray',
        };
    }

    // Optional: Add custom CSS classes to cards
    protected function getCardExtraAttributes(?object $record): array
    {
        $classes = [];

        // Add special styling for overdue tasks
        if ($record->due_date && $record->due_date->isPast() && $record->status !== TaskStatus::Completed) {
            $classes[] = 'border-red-300 bg-red-50';
        }

        // Add special styling for high priority tasks
        if ($record->priority === 'urgent') {
            $classes[] = 'ring-2 ring-red-200';
        }

        return [
            'class' => implode(' ', $classes),
        ];
    }

    // Optional: Customize modal titles
    protected function getEditModalTitle(): string
    {
        return 'Edit Task';
    }

    protected function getCreateModalTitle(): string
    {
        return 'Create New Task';
    }

    // Optional: Add confirmation for sensitive actions
    protected function getDeleteAction(): array
    {
        if (!auth()->user()->isAdmin()) {
            return [];
        }

        return [
            'delete' => [
                'label' => 'Delete Task',
                'icon' => 'heroicon-o-trash',
                'color' => 'danger',
                'requiresConfirmation' => true,
                'modalHeading' => 'Delete Task',
                'modalDescription' => 'Are you sure you want to delete this task? This action cannot be undone.',
                'modalSubmitActionLabel' => 'Yes, delete it',
            ],
        ];
    }
}
