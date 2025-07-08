<?php

namespace App\Filament\Widgets;

use App\TaskStatus;
use App\Models\Task;
use Filament\Widgets\ChartWidget;

class TaskProgressChart extends ChartWidget
{
    protected static ?string $heading = 'Task Progress';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();
        $baseQuery = Task::query()->forUser($user);

        // Clone the query for each count to avoid conflicts
        $pendingCount = (clone $baseQuery)->where('status', TaskStatus::Pending)->count();
        $inProgressCount = (clone $baseQuery)->where('status', TaskStatus::InProgress)->count();
        $completedCount = (clone $baseQuery)->where('status', TaskStatus::Completed)->count();

        $data = [
            'labels' => ['Pending', 'In Progress', 'Completed'],
            'datasets' => [
                [
                    'label' => $user->isAdmin() ? 'All Tasks' : 'Your Tasks',
                    'data' => [
                        $pendingCount,
                        $inProgressCount,
                        $completedCount,
                    ],
                    'backgroundColor' => [
                        'rgb(156, 163, 175)', // gray for pending
                        'rgb(251, 191, 36)',  // yellow for in progress
                        'rgb(34, 197, 94)',   // green for completed
                    ],
                    'borderColor' => [
                        'rgb(107, 114, 128)',
                        'rgb(245, 158, 11)',
                        'rgb(22, 163, 74)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
        ];

        return $data;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    // Add polling to auto-refresh the chart
    protected static ?string $pollingInterval = '15s';

    // Make the widget refreshable
    protected static bool $isLazy = false;

    // Optional: Add chart options for better appearance
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'cutout' => '60%', // Makes it a proper doughnut chart
        ];
    }

    // Optional: Customize the chart description
    public function getDescription(): ?string
    {
        $user = auth()->user();
        $total = Task::query()->forUser($user)->count();

        if ($total === 0) {
            return 'No tasks available';
        }

        return $user->isAdmin()
            ? "Distribution of all {$total} tasks in the system"
            : "Distribution of your {$total} assigned tasks";
    }

    // Optional: Add a filter or extra functionality
    public function getHeading(): ?string
    {
        $user = auth()->user();
        $total = Task::query()->forUser($user)->count();

        return "Task Progress ({$total} total)";
    }
}
