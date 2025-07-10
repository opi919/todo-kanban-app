<?php

namespace App\Filament\Pages;

use App\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class TasksKanbanBoard extends KanbanBoard
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationGroup = 'Task Management';
    protected static ?int $navigationSort = 1;
    protected static string $model = Task::class;
    protected static string $statusEnum = TaskStatus::class;

    public static function getStatuses(): array
    {
        return collect(TaskStatus::cases())
            ->reject(fn($status) => $status === TaskStatus::waiting)
            ->map(fn($status) => $status->value)
            ->toArray();
    }

    protected function getEloquentQuery(): Builder
    {
        return Task::query()->forUser(auth()->user())->with(['assignedUsers', 'creator', 'organization']);
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
            CreateAction::make()
                ->model(Task::class)
                ->form($this->getCreateModalFormSchema())
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = TaskStatus::Pending->value;
                    $data['created_by'] = auth()->id();
                    $data['organization_id'] = auth()->user()->organization_id;
                    return $data;
                })
                ->after(function (Task $record, array $data) {
                    if (isset($data['assignedUsers']) && is_array($data['assignedUsers'])) {
                        $record->assignedUsers()->sync($data['assignedUsers']);
                    }
                    $this->dispatch('refresh');
                }),
        ];
    }

    protected function createRecord(array $data): void
    {
        $data['status'] = TaskStatus::Pending->value;
        $data['created_by'] = auth()->id();
        $data['organization_id'] = auth()->user()->organization_id;

        // Extract assigned users before creating task
        $assignedUsers = $data['assignedUsers'] ?? [];
        unset($data['assignedUsers']);

        $task = Task::create($data);

        // Attach assigned users
        if (!empty($assignedUsers)) {
            $task->assignedUsers()->sync($assignedUsers);
        }

        Notification::make()
            ->title('Task Created')
            ->body("Task '{$task->title}' has been created successfully.")
            ->success()
            ->send();
    }

    protected function getEditModalFormSchema(string|int|null $recordId): array
    {
        $user = auth()->user();
        $task = Task::find($recordId);

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

                Select::make('assignedUsers')
                    ->label('Assigned To')
                    ->options($user->getAssignableUsersQuery()->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->default($task ? $task->assignedUsers->pluck('id')->toArray() : []),

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
        $user = auth()->user();

        if (!$user->isAdmin()) {
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

            Select::make('assignedUsers')
                ->label('Assigned To')
                ->options($user->getAssignableUsersQuery()->pluck('name', 'id'))
                ->multiple()
                ->searchable()
                ->preload(),

            DatePicker::make('due_date'),

            Hidden::make('created_by')
                ->default($user->id),

            Hidden::make('organization_id')
                ->default($user->organization_id),
        ];
    }

    public function onStatusChanged(string|int $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $task = Task::find($recordId);

        // Check if user can modify this task
        if (!$task->canBeEditedBy(auth()->user())) {
            Notification::make()
                ->title('Access Denied')
                ->body('You can only move tasks assigned to you or in your organization.')
                ->danger()
                ->send();
            return;
        }

        $task->update(['status' => $status]);
        Task::setNewOrder($toOrderedIds);

        Notification::make()
            ->title('Task Updated')
            ->body("Task '{$task->title}' moved to " . TaskStatus::from($status)->getLabel())
            ->success()
            ->send();
    }

    public function onSortChanged(string|int $recordId, string $status, array $orderedIds): void
    {
        $task = Task::find($recordId);

        // Check if user can modify this task
        if (!$task->canBeEditedBy(auth()->user())) {
            Notification::make()
                ->title('Access Denied')
                ->body('You can only reorder tasks assigned to you or in your organization.')
                ->danger()
                ->send();
            return;
        }

        Task::setNewOrder($orderedIds);
    }

    protected function editRecord($recordId, array $data, array $state): void
    {
        $task = Task::find($recordId);

        // Check if user can edit this task
        if (!$task->canBeEditedBy(auth()->user())) {
            Notification::make()
                ->title('Access Denied')
                ->body('You can only edit tasks assigned to you or in your organization.')
                ->danger()
                ->send();
            return;
        }

        // Extract assigned users before updating
        $assignedUsers = $data['assignedUsers'] ?? null;
        unset($data['assignedUsers']);

        // For regular users, only allow status updates
        if (!auth()->user()->isAdmin()) {
            $task->update([
                'status' => $data['status'] ?? $task->status,
            ]);
        } else {
            // Admins can update all fields
            $task->update($data);

            // Update assigned users if provided
            if ($assignedUsers !== null) {
                $task->assignedUsers()->sync($assignedUsers);
            }
        }

        Notification::make()
            ->title('Task Updated')
            ->body("Task '{$task->title}' has been updated successfully.")
            ->success()
            ->send();
    }

    protected function getRecordTitle(?object $record): string
    {
        return $record->title ?? 'Untitled Task';
    }

    protected function getCardSubtitle(?object $record): string
    {
        $subtitle = [];

        // Show assigned users
        if ($record->assignedUsers && $record->assignedUsers->count() > 0) {
            $userNames = $record->assignedUsers->pluck('name')->take(2)->join(', ');
            if ($record->assignedUsers->count() > 2) {
                $userNames .= ' +' . ($record->assignedUsers->count() - 2);
            }
            $subtitle[] = "👥 {$userNames}";
        } else {
            $subtitle[] = "👤 Unassigned";
        }

        // Show organization for super admin
        if (auth()->user()->isSuperAdmin() && $record->organization) {
            $subtitle[] = "🏢 {$record->organization->name}";
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

        // Add styling for unassigned tasks (admin view)
        if (auth()->user()->isAdmin() && $record->assignedUsers->count() === 0) {
            $classes[] = 'border-dashed border-gray-300';
        }

        return [
            'class' => implode(' ', $classes),
        ];
    }

    protected function canEdit(object $record): bool
    {
        return $record->canBeEditedBy(auth()->user());
    }

    protected function canDelete(object $record): bool
    {
        return auth()->user()->isAdmin() && $record->canBeEditedBy(auth()->user());
    }

    protected function canCreate(): bool
    {
        return auth()->user()->isAdmin();
    }
}
