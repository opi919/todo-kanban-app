<?php

namespace App\Filament\Widgets;

use App\Models\Todo;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TodoStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        // Base query: all tasks for admin, own tasks for others
        $query = Todo::query();

        if (! $user->is_admin) {
            $query->where('user_id', $user->id);
        }

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $overdue = (clone $query)
            ->where('status', '!=', 'completed')
            ->whereDate('due_date', '<', Carbon::today())
            ->count();

        $progress = $total ? round(($completed / $total) * 100) : 0;

        return [
            Card::make('Total Tasks', $total),
            Card::make('Completed Tasks', $completed),
            Card::make('Pending Tasks', $pending),
            Card::make('Overdue Tasks', $overdue),
            Card::make('Completion Rate', $progress . '%')
                ->chart([
                    $progress,
                    100 - $progress,
                ])
                ->color('success'),
        ];
    }
}
