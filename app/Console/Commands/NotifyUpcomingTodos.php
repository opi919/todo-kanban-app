<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Todo;
use App\Notifications\TodoDueSoonNotification;
use Carbon\Carbon;

class NotifyUpcomingTodos extends Command
{
    protected $signature = 'notify:due-todos';
    protected $description = 'Notify users about tasks due tomorrow';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow();

        $todos = Todo::whereDate('due_date', $tomorrow)
            ->where('is_completed', false)
            ->with('user')
            ->get();

        foreach ($todos as $todo) {
            if ($todo->user) {
                $todo->user->notify(new TodoDueSoonNotification($todo));
            }
        }

        $this->info('Notifications sent.');
        return Command::SUCCESS;
    }
}
