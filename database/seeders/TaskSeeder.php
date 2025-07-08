<?php

namespace Database\Seeders;

use App\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $users = User::where('role', 'user')->get();

        $tasks = [
            [
                'title' => 'Setup project repository',
                'description' => 'Initialize Git repository and setup basic project structure',
                'status' => TaskStatus::Completed,
                'priority' => 'high',
                'due_date' => now()->subDays(2),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Design database schema',
                'description' => 'Create ERD and design database tables for the application',
                'status' => TaskStatus::Completed,
                'priority' => 'medium',
                'due_date' => now()->subDay(),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Implement user authentication',
                'description' => 'Setup Laravel authentication with Filament admin panel',
                'status' => TaskStatus::InProgress,
                'priority' => 'high',
                'due_date' => now()->addDay(),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Create task management interface',
                'description' => 'Build Kanban board interface using Filament-Kanban',
                'status' => TaskStatus::InProgress,
                'priority' => 'medium',
                'due_date' => now()->addDays(3),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Add drag and drop functionality',
                'description' => 'Implement drag and drop for task status changes',
                'status' => TaskStatus::Pending,
                'priority' => 'medium',
                'due_date' => now()->addDays(5),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Write unit tests',
                'description' => 'Create comprehensive test suite for all features',
                'status' => TaskStatus::InProgress,
                'priority' => 'low',
                'due_date' => now()->addWeek(),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Deploy to production',
                'description' => 'Setup production environment and deploy application',
                'status' => TaskStatus::InProgress,
                'priority' => 'urgent',
                'due_date' => now()->addDays(10),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Code Pending process',
                'description' => 'Pending all code changes before merging',
                'status' => TaskStatus::Pending,
                'priority' => 'high',
                'due_date' => now()->addDays(2),
                'assigned_to' => $users->random()->id,
            ],
            [
                'title' => 'Update documentation',
                'description' => 'Update project documentation with new features',
                'status' => TaskStatus::InProgress,
                'priority' => 'medium',
                'due_date' => now()->addDays(7),
                'assigned_to' => $users->random()->id,
            ],
        ];

        foreach ($tasks as $index => $task) {
            Task::create([
                ...$task,
                'created_by' => $admin->id,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
