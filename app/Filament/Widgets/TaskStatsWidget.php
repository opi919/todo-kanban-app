<?php

namespace App\Filament\Widgets;

use App\TaskStatus; // This should be App\Enums\TaskStatus if TaskStatus is in Enums folder
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaskStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $baseQuery = Task::query()->forUser($user);

        // Clone the query for each count to avoid conflicts
        $totalTasks = (clone $baseQuery)->count();
        $todoTasks = (clone $baseQuery)->where('status', TaskStatus::Pending)->count();
        $inProgressTasks = (clone $baseQuery)->where('status', TaskStatus::InProgress)->count();
        $completedTasks = (clone $baseQuery)->where('status', TaskStatus::Completed)->count();

        $stats = [
            Stat::make('Total Tasks', $totalTasks)
                ->description($user->isAdmin() ? 'All tasks in system' : 'Your assigned tasks')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Pending', $todoTasks)
                ->description('Pending tasks')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('In Progress', $inProgressTasks)
                ->description('Active tasks')
                ->descriptionIcon('heroicon-m-play')
                ->color('info'),

            Stat::make('Completed', $completedTasks)
                ->description('Finished tasks')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];

        // Add admin-specific stats
        if ($user->isAdmin()) {
            $overdueTasks = Task::query()
                ->where('due_date', '<', now())
                ->whereNotIn('status', [TaskStatus::Completed])
                ->count();

            $stats[] = Stat::make('Overdue', $overdueTasks)
                ->description('Tasks past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        }

        return $stats;
    }

    // Add polling to auto-refresh the widget
    protected static ?string $pollingInterval = '10s';

    // Make the widget refreshable
    protected static bool $isLazy = false;
}
